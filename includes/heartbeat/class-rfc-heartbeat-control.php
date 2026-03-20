<?php
defined('ABSPATH') || exit;

final class RFC_Heartbeat_Control {

    private $settings;
    private $context;

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        if ($this->settings->isSafeMode()) {
            return;
        }

        add_action('init', [$this, 'detectContext'], 1);
        add_action('admin_enqueue_scripts', [$this, 'controlAdmin'], 99);
        add_action('wp_enqueue_scripts', [$this, 'controlFrontend'], 99);
        add_filter('heartbeat_settings', [$this, 'adjustInterval'], 99);
    }

    public function detectContext() {
        if (!is_admin()) {
            $this->context = 'frontend';
            return;
        }

        global $pagenow;
        $editors = ['post.php', 'post-new.php'];

        if (in_array($pagenow, $editors, true)) {
            $this->context = 'editor';
        } else {
            $this->context = 'dashboard';
        }
    }

    public function controlAdmin() {
        if ($this->context === 'editor') {
            $mode = $this->settings->get('heartbeat_editor', 'reduce');
            if ($mode === 'disable') {
                wp_deregister_script('heartbeat');
            }
            return;
        }

        $mode = $this->settings->get('heartbeat_dashboard', 'reduce');
        if ($mode === 'disable') {
            wp_deregister_script('heartbeat');
        }
    }

    public function controlFrontend() {
        $mode = $this->settings->get('heartbeat_frontend', 'disable');
        if ($mode === 'disable') {
            wp_deregister_script('heartbeat');
        }
    }

    public function adjustInterval($settings) {
        $key = $this->resolveSettingKey();
        $mode = $this->settings->get($key, 'reduce');

        if ($mode === 'disable') {
            return $settings;
        }

        if ($mode === 'reduce') {
            $freq = (int) $this->settings->get('heartbeat_frequency', 60);
            $freq = max(15, min(300, $freq));
            $settings['interval'] = $freq;
            return $settings;
        }

        return $settings;
    }

    private function resolveSettingKey() {
        $map = [
            'frontend'  => 'heartbeat_frontend',
            'editor'    => 'heartbeat_editor',
            'dashboard' => 'heartbeat_dashboard',
        ];

        return $map[$this->context] ?? 'heartbeat_dashboard';
    }
}
