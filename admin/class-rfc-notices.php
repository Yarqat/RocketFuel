<?php
defined('ABSPATH') || exit;

class RFC_Notices {

    private $settings;

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;
        add_action('admin_notices', [$this, 'display_notices']);
    }

    public function display_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->notice_conflicts();
        $this->notice_wp_cache_constant();
        $this->notice_writable();
        $this->notice_foreign_advanced_cache();
        $this->notice_cache_cleared();
        $this->notice_settings_saved();
        $this->notice_license_status();
    }

    private function notice_conflicts() {
        $conflicts = get_option('rfc_conflicts', []);
        if (empty($conflicts)) {
            return;
        }

        $names = implode(', ', array_map('esc_html', $conflicts));
        $this->render(
            'warning',
            sprintf(
                __('RocketFuel Cache detected conflicting plugins: %s. Please deactivate them for best performance.', 'rocketfuel-cache'),
                '<strong>' . $names . '</strong>'
            )
        );
    }

    private function notice_wp_cache_constant() {
        if (defined('WP_CACHE') && WP_CACHE) {
            return;
        }

        if (!$this->settings->get('page_cache_enabled')) {
            return;
        }

        $this->render(
            'warning',
            __('RocketFuel Cache: <code>WP_CACHE</code> is not defined in your wp-config.php. Page caching will not work until this is added.', 'rocketfuel-cache')
        );
    }

    private function notice_writable() {
        if (wp_is_writable(WP_CONTENT_DIR)) {
            return;
        }

        $this->render(
            'error',
            __('RocketFuel Cache: The wp-content directory is not writable. Cache files cannot be created. Please fix file permissions.', 'rocketfuel-cache')
        );
    }

    private function notice_foreign_advanced_cache() {
        $file = WP_CONTENT_DIR . '/advanced-cache.php';
        if (!file_exists($file)) {
            return;
        }

        $contents = file_get_contents($file);
        if ($contents === false || strpos($contents, 'RocketFuel') !== false) {
            return;
        }

        $this->render(
            'warning',
            __('RocketFuel Cache: A foreign <code>advanced-cache.php</code> file was detected. This may have been created by another caching plugin. RocketFuel needs to replace it to function properly.', 'rocketfuel-cache')
        );
    }

    private function notice_cache_cleared() {
        if (!get_transient('rfc_cache_cleared')) {
            return;
        }
        delete_transient('rfc_cache_cleared');
        $this->render('success', __('Cache cleared successfully.', 'rocketfuel-cache'));
    }

    private function notice_settings_saved() {
        if (!get_transient('rfc_settings_saved')) {
            return;
        }
        delete_transient('rfc_settings_saved');
        $this->render('success', __('Settings saved.', 'rocketfuel-cache'));
    }

    private function notice_license_status() {
        $license_status = get_option('rfc_license_status', 'none');
        $trial_end = get_option('rfc_trial_end', 0);

        if ($license_status === 'trial' && $trial_end > 0) {
            $remaining = $trial_end - time();
            if ($remaining <= 0) {
                update_option('rfc_license_status', 'expired');
                $this->render(
                    'warning',
                    sprintf(
                        __('Your RocketFuel Cache trial has expired. <a href="%s">Upgrade to Pro</a> to keep premium features.', 'rocketfuel-cache'),
                        admin_url('admin.php?page=rocketfuel-cache-license')
                    )
                );
                return;
            }

            $days = ceil($remaining / DAY_IN_SECONDS);
            if ($days <= 3) {
                $this->render(
                    'warning',
                    sprintf(
                        __('Your RocketFuel Cache trial expires in %d day(s). <a href="%s">Upgrade now</a> to keep premium features.', 'rocketfuel-cache'),
                        $days,
                        admin_url('admin.php?page=rocketfuel-cache-license')
                    )
                );
            }
            return;
        }

        if ($license_status === 'expired') {
            $this->render(
                'error',
                sprintf(
                    __('Your RocketFuel Cache license has expired. <a href="%s">Renew now</a> to restore Pro features.', 'rocketfuel-cache'),
                    admin_url('admin.php?page=rocketfuel-cache-license')
                )
            );
        }
    }

    private function render($type, $message) {
        printf(
            '<div class="notice notice-%s is-dismissible rfc-notice"><p>%s</p></div>',
            esc_attr($type),
            $message
        );
    }
}
