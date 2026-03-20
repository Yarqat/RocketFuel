<?php
defined('ABSPATH') || exit;

final class RFC_Crash_Guard {

    private $settings;
    private $heartbeat_key = 'rfc_site_heartbeat';
    private $crash_key = 'rfc_crash_detected';
    private $recovery_key = 'rfc_recovery_log';
    private $max_failures = 3;

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        add_action('wp_loaded', [$this, 'recordHeartbeat']);
        add_action('shutdown', [$this, 'checkForFatalError']);
        add_action('admin_init', [$this, 'checkCrashState']);
        add_action('wp_ajax_rfc_dismiss_crash', [$this, 'dismissCrash']);
        add_action('wp_ajax_rfc_test_feature', [$this, 'testFeatureBeforeEnable']);

        register_shutdown_function([$this, 'onShutdown']);

        if ($this->isInRecovery()) {
            $this->enterSafeMode();
        }
    }

    public function recordHeartbeat() {
        if (defined('DOING_AJAX') || defined('DOING_CRON')) {
            return;
        }
        update_option($this->heartbeat_key, [
            'time'   => time(),
            'url'    => $_SERVER['REQUEST_URI'] ?? '/',
            'memory' => memory_get_peak_usage(true),
        ], false);
    }

    public function onShutdown() {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        $fatal_types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
        if (!in_array($error['type'], $fatal_types, true)) {
            return;
        }

        $is_ours = strpos($error['file'], 'rocketfuel-cache') !== false;
        if (!$is_ours) {
            return;
        }

        $failures = (int) get_option('rfc_consecutive_failures', 0);
        $failures++;
        update_option('rfc_consecutive_failures', $failures, false);

        $this->logRecovery([
            'type'    => 'fatal_error',
            'message' => $error['message'],
            'file'    => str_replace(ABSPATH, '', $error['file']),
            'line'    => $error['line'],
            'time'    => time(),
        ]);

        if ($failures >= $this->max_failures) {
            update_option($this->crash_key, [
                'detected_at' => time(),
                'error'       => $error['message'],
                'file'        => str_replace(ABSPATH, '', $error['file']),
                'line'        => $error['line'],
                'failures'    => $failures,
            ], false);

            $this->settings->set('safe_mode', true);
            $this->settings->save();

            update_option('rfc_consecutive_failures', 0, false);
        }
    }

    public function checkForFatalError() {
        if (!is_admin()) {
            return;
        }

        $heartbeat = get_option($this->heartbeat_key, []);
        if (empty($heartbeat) || empty($heartbeat['time'])) {
            return;
        }

        update_option('rfc_consecutive_failures', 0, false);
    }

    public function checkCrashState() {
        $crash = get_option($this->crash_key);
        if (empty($crash)) {
            return;
        }

        add_action('admin_notices', function () use ($crash) {
            $time_ago = human_time_diff($crash['detected_at'], time());
            echo '<div class="notice notice-error rfc-notice" style="border-left-color:#ff3c5f;background:#1a1a2e;color:#e2e2f0;padding:16px;">';
            echo '<h3 style="color:#ff3c5f;margin:0 0 8px;">&#9888; RocketFuel Cache — Crash Detected & Auto-Recovered</h3>';
            echo '<p>A fatal error was detected <strong>' . esc_html($time_ago) . ' ago</strong> in: <code>' . esc_html($crash['file']) . ':' . esc_html($crash['line']) . '</code></p>';
            echo '<p>Error: <code>' . esc_html(substr($crash['error'], 0, 200)) . '</code></p>';
            echo '<p><strong>Safe Mode has been automatically enabled.</strong> All optimizations are paused. Your site is running normally.</p>';
            echo '<p>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=rocketfuel-cache&tab=tools')) . '" class="button" style="margin-right:8px;">Review Settings</a>';
            echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin-ajax.php?action=rfc_dismiss_crash'), 'rfc_dismiss_crash')) . '" class="button">Dismiss & Disable Safe Mode</a>';
            echo '</p>';
            echo '</div>';
        });
    }

    public function dismissCrash() {
        check_ajax_referer('rfc_dismiss_crash');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        delete_option($this->crash_key);
        $this->settings->set('safe_mode', false);
        $this->settings->save();
        wp_safe_redirect(admin_url('admin.php?page=rocketfuel-cache'));
        exit;
    }

    public function testFeatureBeforeEnable() {
        check_ajax_referer('rfc_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $feature = sanitize_text_field($_POST['feature'] ?? '');
        $result = $this->analyzeFeatureRisk($feature);
        wp_send_json_success($result);
    }

    public function analyzeFeatureRisk($feature) {
        $risks = [];
        $safe = true;
        $warnings = [];

        switch ($feature) {
            case 'combine_css':
                $active_plugins = get_option('active_plugins', []);
                foreach ($active_plugins as $p) {
                    if (strpos($p, 'elementor') !== false) {
                        $warnings[] = 'Elementor detected. CSS combining may cause styling issues. Test thoroughly.';
                    }
                    if (strpos($p, 'divi') !== false) {
                        $warnings[] = 'Divi detected. CSS combining often conflicts with Divi\'s dynamic CSS.';
                    }
                }
                if (empty($warnings)) {
                    $warnings[] = 'CSS combining can occasionally break layouts. A backup snapshot will be taken.';
                }
                break;

            case 'combine_js':
                $warnings[] = 'JavaScript combining can break functionality if scripts have load-order dependencies.';
                $warnings[] = 'jQuery is automatically excluded. If issues occur, add problematic scripts to the exclusion list.';
                break;

            case 'minify_js':
            case 'minify_css':
                $warnings[] = 'Minification is generally safe. If a specific file breaks, add it to the exclusion list.';
                break;

            case 'defer_js':
                if (class_exists('WooCommerce')) {
                    $warnings[] = 'WooCommerce detected. Cart functionality may need jQuery excluded from deferring (already excluded by default).';
                }
                break;

            case 'delay_js':
                $warnings[] = 'Delay JS pauses all matched scripts until user interaction. Analytics and chat widgets may load late.';
                $warnings[] = 'If critical functionality breaks, add the script keyword to the exclusion list.';
                if (class_exists('WooCommerce')) {
                    $warnings[] = 'WooCommerce AJAX add-to-cart may be delayed. Consider excluding wc-add-to-cart.';
                }
                break;

            case 'critical_css':
                $warnings[] = 'Critical CSS generation takes 30-60 seconds per template. It runs in the background.';
                $warnings[] = 'If styles look broken on first load, wait for generation to complete and clear cache.';
                break;

            case 'remove_unused_css':
                $warnings[] = 'Unused CSS removal is aggressive. Dynamic elements (popups, modals, sliders) may lose styling.';
                $warnings[] = 'Add critical CSS selectors to the SafeList if elements break.';
                $safe = false;
                $risks[] = 'May remove CSS needed for JavaScript-triggered elements';
                break;

            case 'disable_xml_rpc':
                if (class_exists('Jetpack')) {
                    $warnings[] = 'Jetpack detected! Disabling XML-RPC will break Jetpack connection.';
                    $safe = false;
                    $risks[] = 'Jetpack requires XML-RPC to function';
                }
                break;

            case 'disable_rest_api_public':
                $cf7 = false;
                foreach (get_option('active_plugins', []) as $p) {
                    if (strpos($p, 'contact-form-7') !== false) $cf7 = true;
                }
                if ($cf7) {
                    $warnings[] = 'Contact Form 7 detected. CF7 uses REST API for form submissions. Add its endpoints to the allowlist.';
                }
                $warnings[] = 'Some plugins and themes use the REST API on the frontend. Test all forms and interactive features.';
                break;

            case 'disable_comments':
                global $wpdb;
                $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = '1'");
                if ($count > 0) {
                    $warnings[] = "Your site has {$count} approved comments. Disabling will hide all of them from the frontend.";
                }
                break;

            case 'disable_rss':
                $warnings[] = 'If you have email subscribers using RSS (Mailchimp RSS campaigns, etc.), they will stop receiving updates.';
                break;

            case 'disable_embeds':
                $warnings[] = 'WordPress auto-embeds for YouTube, Twitter, etc. will stop working in post content. Manual iframe embeds are not affected.';
                break;

            case 'change_login_url':
                $warnings[] = 'IMPORTANT: Bookmark your new login URL. The old wp-login.php will return 404.';
                $warnings[] = 'If you forget the new URL, add ?rfc_reset_login=1 to your site URL to temporarily restore wp-login.php.';
                $risks[] = 'You may lock yourself out if you forget the custom login URL';
                break;

            case 'disable_google_fonts':
                $theme = wp_get_theme();
                $warnings[] = 'Your theme (' . $theme->get('Name') . ') may use Google Fonts. Text may fall back to system fonts.';
                break;

            default:
                $warnings[] = 'This feature is generally safe to enable.';
        }

        return [
            'feature'  => $feature,
            'safe'     => $safe && empty($risks),
            'warnings' => $warnings,
            'risks'    => $risks,
            'action'   => empty($risks) ? 'You can safely enable this feature.' : 'Proceed with caution. Test your site after enabling.',
        ];
    }

    public function isInRecovery() {
        $crash = get_option($this->crash_key);
        return !empty($crash);
    }

    private function enterSafeMode() {
        if (!$this->settings->get('safe_mode', false)) {
            $this->settings->set('safe_mode', true);
            $this->settings->save();
        }
    }

    private function logRecovery($entry) {
        $log = get_option($this->recovery_key, []);
        array_unshift($log, $entry);
        $log = array_slice($log, 0, 50);
        update_option($this->recovery_key, $log, false);
    }

    public function getRecoveryLog() {
        return get_option($this->recovery_key, []);
    }

    public static function takeSnapshot() {
        $snapshot = [
            'time'     => time(),
            'settings' => RFC_Settings::instance()->all(),
            'version'  => RFC_VERSION,
        ];
        update_option('rfc_settings_snapshot', $snapshot, false);
    }

    public static function restoreSnapshot() {
        $snapshot = get_option('rfc_settings_snapshot');
        if (empty($snapshot) || empty($snapshot['settings'])) {
            return false;
        }

        $settings = RFC_Settings::instance();
        foreach ($snapshot['settings'] as $key => $value) {
            $settings->set($key, $value);
        }
        $settings->save();
        return true;
    }
}
