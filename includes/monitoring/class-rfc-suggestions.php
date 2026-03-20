<?php
defined('ABSPATH') || exit;

class RFC_Suggestions {

    private $settings;

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;
        add_action('wp_ajax_rfc_apply_suggestion', [$this, 'ajaxApplySuggestion']);
    }

    public function analyze() {
        $suggestions = [];

        $suggestions[] = $this->check(
            'critical_css',
            'Enable Critical CSS',
            'Generate and inline critical above-the-fold CSS to eliminate render-blocking stylesheets.',
            'high',
            [15, 25],
            'critical_css_enabled',
            true,
            true
        );

        $suggestions[] = $this->check(
            'delay_js',
            'Enable Delay JavaScript',
            'Delay loading of JavaScript until user interaction to improve initial page load speed.',
            'high',
            [10, 20],
            'delay_js_enabled',
            true,
            true
        );

        $suggestions[] = $this->check(
            'unused_css',
            'Remove Unused CSS',
            'Scan pages and strip CSS rules that are not used, reducing total CSS payload significantly.',
            'high',
            [10, 15],
            'remove_unused_css_enabled',
            true,
            true
        );

        $suggestions[] = $this->check(
            'page_cache',
            'Enable Page Cache',
            'Serve static HTML files instead of processing PHP on every request. This is the single biggest performance gain.',
            'high',
            [20, 30],
            'page_cache_enabled',
            true,
            false
        );

        $suggestions[] = $this->check(
            'minify_css',
            'Minify CSS Files',
            'Remove whitespace and comments from CSS files to reduce file size.',
            'medium',
            [5, 10],
            'minify_css',
            true,
            false
        );

        $suggestions[] = $this->check(
            'minify_js',
            'Minify JavaScript Files',
            'Remove whitespace and comments from JavaScript files to reduce file size.',
            'medium',
            [5, 10],
            'minify_js',
            true,
            false
        );

        $suggestions[] = $this->check(
            'defer_js',
            'Defer JavaScript Loading',
            'Add defer attribute to JavaScript files so they do not block page rendering.',
            'medium',
            [5, 15],
            'defer_js',
            true,
            false
        );

        $suggestions[] = $this->check(
            'lazy_load',
            'Enable Lazy Loading for Images',
            'Load images only when they enter the viewport to reduce initial page weight.',
            'medium',
            [5, 10],
            'lazy_load_images',
            true,
            false
        );

        $suggestions[] = $this->check(
            'local_fonts',
            'Host Google Fonts Locally',
            'Download and serve Google Fonts from your server to eliminate external requests and improve privacy.',
            'medium',
            [3, 8],
            'local_google_fonts',
            true,
            false
        );

        $suggestions[] = $this->check(
            'disable_emojis',
            'Disable WordPress Emoji Script',
            'Remove the wp-emoji-release.min.js script that WordPress loads on every page.',
            'low',
            [1, 3],
            'disable_emojis',
            true,
            false
        );

        $suggestions[] = $this->check(
            'disable_dashicons',
            'Disable Dashicons on Frontend',
            'Dashicons CSS is loaded on every page for logged-out users even when not needed.',
            'low',
            [1, 3],
            'disable_dashicons',
            true,
            false
        );

        $suggestions[] = $this->check(
            'query_strings',
            'Remove Query Strings from Static Resources',
            'Remove version query strings from CSS and JS files to improve caching by CDNs and proxies.',
            'low',
            [1, 2],
            'remove_query_strings',
            true,
            false
        );

        $suggestions[] = $this->check(
            'jquery_migrate',
            'Remove jQuery Migrate',
            'jQuery Migrate is loaded for backward compatibility but most modern themes and plugins do not need it.',
            'low',
            [1, 3],
            'remove_jquery_migrate',
            true,
            false
        );

        $suggestions[] = $this->checkYoutube();

        $suggestions[] = $this->check(
            'webp',
            'Enable WebP Image Conversion',
            'Convert images to WebP format for significantly smaller file sizes with no quality loss.',
            'medium',
            [5, 10],
            'webp_conversion',
            true,
            true
        );

        $suggestions[] = $this->checkGzip();
        $suggestions[] = $this->checkBrowserCaching();
        $suggestions[] = $this->checkRevisions();

        $suggestions[] = $this->check(
            'heartbeat',
            'Control WordPress Heartbeat API',
            'The Heartbeat API makes regular AJAX calls that can slow down the admin and frontend.',
            'low',
            [1, 2],
            'heartbeat_frontend',
            'disable',
            false
        );

        $suggestions[] = $this->checkWooCommerce();

        $suggestions = array_filter($suggestions);

        usort($suggestions, function ($a, $b) {
            $order = ['high' => 0, 'medium' => 1, 'low' => 2];
            $aOrder = $order[$a['impact']] ?? 3;
            $bOrder = $order[$b['impact']] ?? 3;
            if ($aOrder === $bOrder) {
                return $b['points'] - $a['points'];
            }
            return $aOrder - $bOrder;
        });

        return $suggestions;
    }

    private function check($id, $title, $description, $impact, $points_range, $setting_key, $setting_value, $pro_required) {
        $current = $this->settings->get($setting_key, false);

        if ($current == $setting_value) {
            return null;
        }

        $points = (int) round(($points_range[0] + $points_range[1]) / 2);

        return [
            'id'            => $id,
            'title'         => $title,
            'description'   => $description,
            'impact'        => $impact,
            'points'        => $points,
            'setting_key'   => $setting_key,
            'setting_value' => $setting_value,
            'status'        => 'actionable',
            'pro_required'  => $pro_required,
        ];
    }

    private function checkYoutube() {
        $current = $this->settings->get('youtube_thumbnail_swap', false);
        if ($current) {
            return null;
        }

        global $wpdb;
        $has_youtube = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%youtube.com%' OR post_content LIKE '%youtu.be%' LIMIT 1"
        );

        if (!$has_youtube) {
            return null;
        }

        return [
            'id'            => 'youtube_swap',
            'title'         => 'Enable YouTube Thumbnail Swap',
            'description'   => 'Your site has YouTube embeds. Replace iframes with lightweight thumbnails that load the player on click.',
            'impact'        => 'medium',
            'points'        => 8,
            'setting_key'   => 'youtube_thumbnail_swap',
            'setting_value' => true,
            'status'        => 'actionable',
            'pro_required'  => false,
        ];
    }

    private function checkGzip() {
        $response = wp_remote_get(home_url('/'), [
            'timeout'   => 10,
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $headers = wp_remote_retrieve_headers($response);
        $encoding = $headers['content-encoding'] ?? '';

        if (strpos($encoding, 'gzip') !== false || strpos($encoding, 'br') !== false) {
            return null;
        }

        return [
            'id'            => 'gzip',
            'title'         => 'Enable GZIP Compression',
            'description'   => 'Your server is not sending compressed responses. Enable GZIP to reduce transfer size by 60-80%.',
            'impact'        => 'high',
            'points'        => 13,
            'setting_key'   => '',
            'setting_value' => null,
            'status'        => 'info',
            'pro_required'  => false,
        ];
    }

    private function checkBrowserCaching() {
        $response = wp_remote_get(
            includes_url('js/jquery/jquery.min.js'),
            ['timeout' => 10, 'sslverify' => false]
        );

        if (is_wp_error($response)) {
            return null;
        }

        $headers = wp_remote_retrieve_headers($response);
        $cache_control = $headers['cache-control'] ?? '';
        $expires = $headers['expires'] ?? '';

        if (!empty($cache_control) || !empty($expires)) {
            return null;
        }

        return [
            'id'            => 'browser_caching',
            'title'         => 'Add Browser Caching Headers',
            'description'   => 'Static resources are missing cache headers. Add Expires and Cache-Control headers for better repeat-visit performance.',
            'impact'        => 'medium',
            'points'        => 4,
            'setting_key'   => '',
            'setting_value' => null,
            'status'        => 'info',
            'pro_required'  => false,
        ];
    }

    private function checkRevisions() {
        global $wpdb;
        $count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'"
        );

        if ($count <= 100) {
            return null;
        }

        return [
            'id'            => 'revisions',
            'title'         => 'Clean Up Post Revisions',
            'description'   => sprintf('Your database has %s revisions. Cleaning them up will reduce database size and speed up queries.', number_format_i18n($count)),
            'impact'        => 'low',
            'points'        => 2,
            'setting_key'   => 'db_revisions',
            'setting_value' => true,
            'status'        => 'actionable',
            'pro_required'  => false,
        ];
    }

    private function checkWooCommerce() {
        if (!class_exists('WooCommerce')) {
            return null;
        }

        $current = $this->settings->get('disable_wc_bloat', false);
        if ($current) {
            return null;
        }

        return [
            'id'            => 'wc_scripts',
            'title'         => 'Limit WooCommerce Scripts to Shop Pages',
            'description'   => 'WooCommerce loads cart fragments and scripts on every page. Restrict them to shop and cart pages only.',
            'impact'        => 'medium',
            'points'        => 8,
            'setting_key'   => 'disable_wc_bloat',
            'setting_value' => true,
            'status'        => 'actionable',
            'pro_required'  => false,
        ];
    }

    public function getTotalPotentialPoints() {
        $suggestions = $this->analyze();
        $total = 0;
        foreach ($suggestions as $s) {
            if ($s['status'] === 'actionable') {
                $total += $s['points'];
            }
        }
        return $total;
    }

    public function getEstimatedScore($current_score) {
        $potential = $this->getTotalPotentialPoints();
        return min(100, $current_score + $potential);
    }

    public function ajaxApplySuggestion() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $suggestion_id = isset($_POST['suggestion_id']) ? sanitize_key($_POST['suggestion_id']) : '';

        if (empty($suggestion_id)) {
            wp_send_json_error('Missing suggestion ID');
        }

        $suggestions = $this->analyze();
        $target = null;

        foreach ($suggestions as $s) {
            if ($s['id'] === $suggestion_id) {
                $target = $s;
                break;
            }
        }

        if (!$target) {
            wp_send_json_error('Suggestion not found or already applied');
        }

        if ($target['pro_required'] && !(RFC_Engine::instance() && RFC_Engine::instance()->hasPro())) {
            wp_send_json_error('This feature requires RocketFuel Pro');
        }

        if (empty($target['setting_key']) || $target['status'] !== 'actionable') {
            wp_send_json_error('This suggestion cannot be auto-applied');
        }

        $this->settings->set($target['setting_key'], $target['setting_value'])->save();

        wp_send_json_success([
            'id'      => $target['id'],
            'applied' => true,
            'message' => sprintf('%s has been enabled.', $target['title']),
        ]);
    }
}
