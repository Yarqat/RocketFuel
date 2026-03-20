<?php
/**
 * Plugin Name: RocketFuel Cache
 * Plugin URI: https://shahfahad.info/rocketfuel-cache
 * Description: The only performance plugin you need. Replaces 9 plugins. Page caching, image optimization, CSS/JS minification, lazy loading, and 160+ features.
 * Version: 1.0.0
 * Author: Shah Fahad
 * Author URI: https://shahfahad.info
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rocketfuel-cache
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Tested up to: 6.7
 */

defined('ABSPATH') || exit;

define('RFC_VERSION', '1.0.0');
define('RFC_FILE', __FILE__);
define('RFC_PATH', plugin_dir_path(__FILE__));
define('RFC_URL', plugin_dir_url(__FILE__));
define('RFC_BASENAME', plugin_basename(__FILE__));
define('RFC_CACHE_DIR', WP_CONTENT_DIR . '/cache/rocketfuel/');
define('RFC_MIN_DIR', WP_CONTENT_DIR . '/cache/rocketfuel/min/');
define('RFC_SLUG', 'rocketfuel-cache');

require_once RFC_PATH . 'includes/class-rfc-autoloader.php';
RFC_Autoloader::init();
require_once RFC_PATH . 'includes/class-rfc-helpers.php';

register_activation_hook(__FILE__, function () {
    try {
        RFC_Activator::run();
    } catch (\Throwable $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('RocketFuel activation error: ' . $e->getMessage());
        }
    }
});

register_deactivation_hook(__FILE__, function () {
    try {
        RFC_Deactivator::run();
    } catch (\Throwable $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('RocketFuel deactivation error: ' . $e->getMessage());
        }
    }
});

add_action('plugins_loaded', function () {
    try {
        RFC_Engine::ignite();
    } catch (\Throwable $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('RocketFuel boot error: ' . $e->getMessage());
        }
        add_action('admin_notices', function () use ($e) {
            echo '<div class="notice notice-error"><p>RocketFuel Cache encountered an error: ' . esc_html($e->getMessage()) . '</p></div>';
        });
    }
});
