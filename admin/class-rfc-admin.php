<?php
defined('ABSPATH') || exit;

class RFC_Admin {

    private $settings;
    private $hook_suffix = [];
    private $tabs = [
        'dashboard'          => 'Dashboard',
        'cache'              => 'Cache',
        'file-optimization'  => 'File Optimization',
        'image-optimization' => 'Image Optimization',
        'media'              => 'Media',
        'preloading'         => 'Preloading',
        'script-manager'     => 'Script Manager',
        'cleanup'            => 'WP Cleanup',
        'database'           => 'Database',
        'cdn'                => 'CDN',
        'heartbeat'          => 'Heartbeat',
        'local-hosting'      => 'Local Hosting',
        'woocommerce'        => 'WooCommerce',
        'security'           => 'Security',
        'monitoring'         => 'Monitoring',
        'reports'            => 'Reports',
        'support'            => 'Support',
        'tools'              => 'Tools',
        'license'            => 'License',
    ];

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'handle_form_submission']);
        add_filter('plugin_action_links_' . RFC_BASENAME, [$this, 'action_links']);
    }

    public function register_menus() {
        $this->hook_suffix[] = add_menu_page(
            'RocketFuel Cache',
            'RocketFuel Cache',
            'manage_options',
            'rocketfuel-cache',
            [$this, 'render_page'],
            'dashicons-performance',
            65
        );

        foreach ($this->tabs as $slug => $label) {
            $menu_slug = $slug === 'dashboard' ? 'rocketfuel-cache' : 'rocketfuel-cache-' . $slug;
            $this->hook_suffix[] = add_submenu_page(
                'rocketfuel-cache',
                $label . ' - RocketFuel Cache',
                $label,
                'manage_options',
                $menu_slug,
                [$this, 'render_page']
            );
        }
    }

    public function enqueue_assets($hook) {
        if (!in_array($hook, $this->hook_suffix, true)) {
            return;
        }

        wp_enqueue_style(
            'rfc-admin',
            RFC_URL . 'assets/css/admin.css',
            [],
            RFC_VERSION
        );

        wp_enqueue_script(
            'rfc-admin',
            RFC_URL . 'assets/js/admin.js',
            ['jquery'],
            RFC_VERSION,
            true
        );

        wp_localize_script('rfc-admin', 'rfcAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('rfc_admin_nonce'),
            'strings' => [
                'confirm_clear'  => __('Are you sure you want to clear all cache?', 'rocketfuel-cache'),
                'confirm_reset'  => __('This will reset ALL settings to defaults. Are you absolutely sure?', 'rocketfuel-cache'),
                'confirm_reset2' => __('Last chance! This cannot be undone. Proceed?', 'rocketfuel-cache'),
                'clearing'       => __('Clearing cache...', 'rocketfuel-cache'),
                'cleared'        => __('Cache cleared successfully!', 'rocketfuel-cache'),
                'saving'         => __('Saving...', 'rocketfuel-cache'),
                'saved'          => __('Settings saved.', 'rocketfuel-cache'),
                'error'          => __('An error occurred. Please try again.', 'rocketfuel-cache'),
                'cleaning'       => __('Running cleanup...', 'rocketfuel-cache'),
                'cleaned'        => __('Cleanup complete!', 'rocketfuel-cache'),
                'preloading'     => __('Preloading cache...', 'rocketfuel-cache'),
                'preloaded'      => __('Preload started.', 'rocketfuel-cache'),
            ],
        ]);
    }

    public function register_settings() {
        register_setting('rfc_settings_group', 'rfc_settings', [
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);
    }

    public function sanitize_settings($input) {
        if (!is_array($input)) {
            return $this->settings->all();
        }
        return $input;
    }

    public function handle_form_submission() {
        if (!isset($_POST['rfc_save_settings'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (!isset($_POST['_rfc_nonce']) || !wp_verify_nonce($_POST['_rfc_nonce'], 'rfc_save_settings')) {
            wp_die(__('Security check failed.', 'rocketfuel-cache'));
        }

        $tab = isset($_POST['rfc_tab']) ? sanitize_key($_POST['rfc_tab']) : 'dashboard';
        $fields = isset($_POST['rfc']) && is_array($_POST['rfc']) ? $_POST['rfc'] : [];
        $defaults = $this->settings->defaults();
        $save_data = [];

        foreach ($defaults as $key => $default_value) {
            if (is_bool($default_value)) {
                $save_data[$key] = isset($fields[$key]) ? true : false;
            } elseif (is_int($default_value)) {
                $save_data[$key] = isset($fields[$key]) ? (int) $fields[$key] : $default_value;
            } elseif (is_string($default_value)) {
                $save_data[$key] = isset($fields[$key]) ? sanitize_textarea_field($fields[$key]) : $default_value;
            }
        }

        $this->settings->bulk($save_data)->save();

        set_transient('rfc_settings_saved', true, 30);

        $redirect = isset($_POST['_wp_http_referer']) ? $_POST['_wp_http_referer'] : admin_url('admin.php?page=rocketfuel-cache');
        wp_safe_redirect(add_query_arg('settings-updated', '1', $redirect));
        exit;
    }

    public function action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=rocketfuel-cache'),
            __('Settings', 'rocketfuel-cache')
        );

        $clear_link = sprintf(
            '<a href="%s" id="rfc-plugin-clear-cache">%s</a>',
            wp_nonce_url(admin_url('admin-ajax.php?action=rfc_purge_all'), 'rfc_admin_nonce'),
            __('Clear Cache', 'rocketfuel-cache')
        );

        array_unshift($links, $settings_link, $clear_link);
        return $links;
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'rocketfuel-cache'));
        }

        $screen = get_current_screen();
        $current_tab = 'dashboard';

        if ($screen && $screen->id !== 'toplevel_page_rocketfuel-cache') {
            foreach ($this->tabs as $slug => $label) {
                if (strpos($screen->id, 'rocketfuel-cache-' . $slug) !== false) {
                    $current_tab = $slug;
                    break;
                }
            }
        }

        $settings = $this->settings;
        $tabs = $this->tabs;

        include RFC_PATH . 'admin/views/partials/header.php';

        $view_file = RFC_PATH . 'admin/views/page-' . $current_tab . '.php';
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<div class="rfc-card"><p>' . esc_html__('This feature is coming soon.', 'rocketfuel-cache') . '</p></div>';
        }

        include RFC_PATH . 'admin/views/partials/footer.php';
    }

    public function get_tabs() {
        return $this->tabs;
    }
}
