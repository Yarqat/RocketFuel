<?php
defined('ABSPATH') || exit;

final class RFC_Conflict_Detector {

    private $settings;
    private $conflicts = [];
    private $optionKey = 'rfc_conflicts';

    private $known = [
        'wp-rocket/wp-rocket.php' => [
            'name' => 'WP Rocket',
            'type' => 'caching',
        ],
        'w3-total-cache/w3-total-cache.php' => [
            'name' => 'W3 Total Cache',
            'type' => 'caching',
        ],
        'wp-super-cache/wp-cache.php' => [
            'name' => 'WP Super Cache',
            'type' => 'caching',
        ],
        'litespeed-cache/litespeed-cache.php' => [
            'name' => 'LiteSpeed Cache',
            'type' => 'caching',
        ],
        'autoptimize/autoptimize.php' => [
            'name' => 'Autoptimize',
            'type' => 'optimization',
        ],
        'wp-fastest-cache/wpFastestCache.php' => [
            'name' => 'WP Fastest Cache',
            'type' => 'caching',
        ],
        'sg-cachepress/sg-cachepress.php' => [
            'name' => 'SG Optimizer',
            'type' => 'caching',
        ],
        'breeze/breeze.php' => [
            'name' => 'Breeze',
            'type' => 'caching',
        ],
        'hummingbird-performance/wp-hummingbird.php' => [
            'name' => 'Hummingbird',
            'type' => 'optimization',
        ],
        'wp-optimize/wp-optimize.php' => [
            'name' => 'WP-Optimize',
            'type' => 'optimization',
        ],
        'flying-scripts/flying-scripts.php' => [
            'name' => 'Flying Scripts',
            'type' => 'optimization',
        ],
        'perfmatters/perfmatters.php' => [
            'name' => 'Perfmatters',
            'type' => 'optimization',
        ],
        'flavor/flavor.php' => [
            'name' => 'Asset CleanUp',
            'type' => 'optimization',
        ],
        'flavor-pro/flavor-pro.php' => [
            'name' => 'Asset CleanUp Pro',
            'type' => 'optimization',
        ],
        'flavor-premium/flavor-premium.php' => [
            'name' => 'Asset CleanUp Pro',
            'type' => 'optimization',
        ],
    ];

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        add_action('admin_init', [$this, 'scan']);
        add_action('admin_notices', [$this, 'showNotice']);
    }

    public function scan() {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $this->conflicts = [];
        $active = get_option('active_plugins', []);

        foreach ($this->known as $plugin => $info) {
            if (in_array($plugin, $active, true) || is_plugin_active($plugin)) {
                $this->conflicts[$plugin] = $info;
            }
        }

        if (is_multisite()) {
            $network_active = get_site_option('active_sitewide_plugins', []);
            foreach ($this->known as $plugin => $info) {
                if (isset($network_active[$plugin]) && !isset($this->conflicts[$plugin])) {
                    $this->conflicts[$plugin] = $info;
                }
            }
        }

        $stored = get_option($this->optionKey, []);
        if ($this->conflicts !== $stored) {
            update_option($this->optionKey, $this->conflicts, false);
        }
    }

    public function showNotice() {
        if (empty($this->conflicts)) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $screen = get_current_screen();
        $dismiss_key = 'rfc_conflict_dismissed';

        if (get_transient($dismiss_key)) {
            return;
        }

        if (isset($_GET['rfc_dismiss_conflict']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'rfc_dismiss_conflict')) {
            set_transient($dismiss_key, 1, DAY_IN_SECONDS * 30);
            return;
        }

        $names = array_column($this->conflicts, 'name');
        $list = implode(', ', $names);

        $dismiss_url = wp_nonce_url(
            add_query_arg('rfc_dismiss_conflict', '1'),
            'rfc_dismiss_conflict'
        );

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>' . esc_html__('RocketFuel Cache', 'rocketfuel-cache') . ':</strong> ';
        echo sprintf(
            esc_html__('Potential conflicts detected with: %s. Running multiple caching or optimization plugins simultaneously may cause unexpected behavior. Consider deactivating conflicting plugins for best results.', 'rocketfuel-cache'),
            '<strong>' . esc_html($list) . '</strong>'
        );
        echo ' <a href="' . esc_url($dismiss_url) . '">' . esc_html__('Dismiss', 'rocketfuel-cache') . '</a>';
        echo '</p></div>';
    }

    public function getConflicts() {
        return $this->conflicts;
    }

    public function hasConflicts() {
        return !empty($this->conflicts);
    }
}
