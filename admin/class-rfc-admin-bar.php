<?php
defined('ABSPATH') || exit;

class RFC_Admin_Bar {

    private $settings;

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;
        add_action('admin_bar_menu', [$this, 'add_menu'], 100);
        add_action('wp_ajax_rfc_purge_all', [$this, 'ajax_purge_all']);
        add_action('wp_ajax_rfc_purge_page', [$this, 'ajax_purge_page']);
        add_action('wp_ajax_rfc_purge_minified', [$this, 'ajax_purge_minified']);
        add_action('wp_ajax_rfc_preload', [$this, 'ajax_preload']);
        add_action('wp_ajax_rfc_toggle_safe_mode', [$this, 'ajax_toggle_safe_mode']);
    }

    public function add_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $wp_admin_bar->add_node([
            'id'    => 'rfc-admin-bar',
            'title' => '<span class="ab-icon dashicons dashicons-performance" style="margin-top:2px;"></span> RocketFuel',
            'href'  => admin_url('admin.php?page=rocketfuel-cache'),
        ]);

        if (!is_admin()) {
            $wp_admin_bar->add_node([
                'id'     => 'rfc-clear-page',
                'parent' => 'rfc-admin-bar',
                'title'  => __('Clear This Page Cache', 'rocketfuel-cache'),
                'href'   => '#',
                'meta'   => [
                    'class' => 'rfc-bar-action',
                    'data'  => 'rfc-purge-page',
                ],
            ]);
        }

        $wp_admin_bar->add_node([
            'id'     => 'rfc-clear-all',
            'parent' => 'rfc-admin-bar',
            'title'  => __('Clear All Cache', 'rocketfuel-cache'),
            'href'   => '#',
            'meta'   => [
                'class' => 'rfc-bar-action',
                'data'  => 'rfc-purge-all',
            ],
        ]);

        $wp_admin_bar->add_node([
            'id'     => 'rfc-clear-minified',
            'parent' => 'rfc-admin-bar',
            'title'  => __('Clear CSS/JS Cache', 'rocketfuel-cache'),
            'href'   => '#',
            'meta'   => [
                'class' => 'rfc-bar-action',
                'data'  => 'rfc-purge-minified',
            ],
        ]);

        $wp_admin_bar->add_node([
            'id'     => 'rfc-preload',
            'parent' => 'rfc-admin-bar',
            'title'  => __('Preload Cache', 'rocketfuel-cache'),
            'href'   => '#',
            'meta'   => [
                'class' => 'rfc-bar-action',
                'data'  => 'rfc-preload',
            ],
        ]);

        $wp_admin_bar->add_node([
            'id'     => 'rfc-separator-1',
            'parent' => 'rfc-admin-bar',
            'title'  => '',
            'meta'   => ['class' => 'rfc-bar-separator'],
        ]);

        $wp_admin_bar->add_node([
            'id'     => 'rfc-settings',
            'parent' => 'rfc-admin-bar',
            'title'  => __('Settings', 'rocketfuel-cache'),
            'href'   => admin_url('admin.php?page=rocketfuel-cache'),
        ]);

        $wp_admin_bar->add_node([
            'id'     => 'rfc-separator-2',
            'parent' => 'rfc-admin-bar',
            'title'  => '',
            'meta'   => ['class' => 'rfc-bar-separator'],
        ]);

        $safe_label = $this->settings->isSafeMode()
            ? __('Disable Safe Mode', 'rocketfuel-cache')
            : __('Enable Safe Mode', 'rocketfuel-cache');

        $wp_admin_bar->add_node([
            'id'     => 'rfc-safe-mode',
            'parent' => 'rfc-admin-bar',
            'title'  => $safe_label,
            'href'   => '#',
            'meta'   => [
                'class' => 'rfc-bar-action',
                'data'  => 'rfc-toggle-safe-mode',
            ],
        ]);
    }

    public function ajax_purge_all() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'rocketfuel-cache')]);
        }

        do_action('rfc_purge_all');
        set_transient('rfc_cache_cleared', true, 30);

        $this->log_event('all_cache_cleared');

        wp_send_json_success(['message' => __('All cache cleared.', 'rocketfuel-cache')]);
    }

    public function ajax_purge_page() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'rocketfuel-cache')]);
        }

        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (empty($url)) {
            wp_send_json_error(['message' => __('No URL provided.', 'rocketfuel-cache')]);
        }

        do_action('rfc_purge_url', $url);

        wp_send_json_success(['message' => __('Page cache cleared.', 'rocketfuel-cache')]);
    }

    public function ajax_purge_minified() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'rocketfuel-cache')]);
        }

        do_action('rfc_purge_minified');

        wp_send_json_success(['message' => __('CSS/JS cache cleared.', 'rocketfuel-cache')]);
    }

    public function ajax_preload() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'rocketfuel-cache')]);
        }

        do_action('rfc_start_preload');

        wp_send_json_success(['message' => __('Cache preloading started.', 'rocketfuel-cache')]);
    }

    public function ajax_toggle_safe_mode() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'rocketfuel-cache')]);
        }

        $current = $this->settings->get('safe_mode');
        $this->settings->set('safe_mode', !$current)->save();

        $status = !$current ? 'enabled' : 'disabled';
        $this->log_event('safe_mode_' . $status);

        wp_send_json_success([
            'message'   => sprintf(__('Safe Mode %s.', 'rocketfuel-cache'), $status),
            'safe_mode' => !$current,
        ]);
    }

    private function log_event($type) {
        $events = get_option('rfc_recent_events', []);
        array_unshift($events, [
            'type' => $type,
            'time' => current_time('timestamp'),
            'user' => get_current_user_id(),
        ]);
        $events = array_slice($events, 0, 50);
        update_option('rfc_recent_events', $events, false);
    }
}
