<?php
defined('ABSPATH') || exit;

final class RFC_Engine {

    private static $instance = null;
    private $modules = [];
    private $settings;
    private $state = 0;

    public static function ignite() {
        if (self::$instance !== null) {
            return self::$instance;
        }
        self::$instance = new self();
        self::$instance->bootstrap();
        return self::$instance;
    }

    public static function instance() {
        return self::$instance;
    }

    private function bootstrap() {
        $this->settings = RFC_Settings::instance();

        if (!$this->preflight()) {
            return;
        }

        $this->state = 1;

        $this->safeLoad('RFC_Crash_Guard', 'crash_guard', [$this->settings]);
        $this->safeLoad('RFC_Page_Cache', 'page_cache', [$this->settings]);
        $this->safeLoad('RFC_Cache_Purge', 'cache_purge', [$this->settings]);
        $this->safeLoad('RFC_Preload', 'preload', [$this->settings]);

        if ($this->settings->get('minify_html', true)) {
            $this->safeLoad('RFC_Minify_HTML', 'minify_html', [$this->settings]);
        }
        if ($this->settings->get('minify_css', true)) {
            $this->safeLoad('RFC_Minify_CSS', 'minify_css', [$this->settings]);
        }
        if ($this->settings->get('minify_js', true)) {
            $this->safeLoad('RFC_Minify_JS', 'minify_js', [$this->settings]);
        }
        if ($this->settings->get('defer_js', true)) {
            $this->safeLoad('RFC_Defer_JS', 'defer_js', [$this->settings]);
        }

        $this->safeLoad('RFC_Lazy_Load', 'lazy_load', [$this->settings]);
        $this->safeLoad('RFC_DNS_Prefetch', 'dns_prefetch', [$this->settings]);
        $this->safeLoad('RFC_Preconnect', 'preconnect', [$this->settings]);
        $this->safeLoad('RFC_Font_Optimization', 'font_optimize', [$this->settings]);

        if (class_exists('RFC_Server_Detector')) {
            $this->modules['server'] = new RFC_Server_Detector();
            $this->safeLoad('RFC_Gzip', 'gzip', [$this->settings, $this->modules['server']]);
            $this->safeLoad('RFC_Htaccess', 'htaccess', [$this->settings, $this->modules['server']]);
        }

        $this->safeLoad('RFC_Cleanup', 'cleanup', [$this->settings]);
        $this->safeLoad('RFC_Image_Dimensions', 'image_dimensions', [$this->settings]);
        $this->safeLoad('RFC_DB_Optimizer', 'db_optimizer', [$this->settings]);
        $this->safeLoad('RFC_Heartbeat_Control', 'heartbeat', [$this->settings]);

        if ($this->settings->get('cdn_enabled', false)) {
            $this->safeLoad('RFC_CDN', 'cdn', [$this->settings]);
        }

        $this->safeLoad('RFC_Security', 'security', [$this->settings]);
        $this->safeLoad('RFC_Speed_Test', 'speed_test', [$this->settings]);
        $this->safeLoad('RFC_Suggestions', 'suggestions', [$this->settings]);
        $this->safeLoad('RFC_Support', 'support', [$this->settings]);
        $this->safeLoad('RFC_Pro_Guard', 'pro_guard', [$this->settings]);
        $this->safeLoad('RFC_Pro_Installer', 'pro_installer', []);

        if (is_admin()) {
            $this->safeLoad('RFC_Admin', 'admin', [$this->settings]);
            $this->safeLoad('RFC_Admin_Bar', 'admin_bar', [$this->settings]);
            $this->safeLoad('RFC_Notices', 'notices', [$this->settings]);
            $this->safeLoad('RFC_Conflict_Detector', 'conflict_detector', [$this->settings]);
            add_action('admin_init', [__CLASS__, 'selfRepair']);
        }

        add_action('rfc_self_check', [__CLASS__, 'selfRepair']);

        if ($this->hasPro()) {
            $this->loadPro();
        }

        $this->state = 2;
        do_action('rfc_loaded');
    }

    private function safeLoad($class, $key, $args = []) {
        try {
            if (!class_exists($class)) {
                return;
            }
            $this->modules[$key] = new $class(...$args);
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('RocketFuel: Failed to load ' . $class . ' - ' . $e->getMessage());
            }
        }
    }

    private function preflight() {
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__('RocketFuel Cache requires PHP 7.4 or higher.', 'rocketfuel-cache');
                echo '</p></div>';
            });
            return false;
        }

        global $wp_version;
        if (version_compare($wp_version, '6.2', '<')) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__('RocketFuel Cache requires WordPress 6.2 or higher.', 'rocketfuel-cache');
                echo '</p></div>';
            });
            return false;
        }

        return true;
    }

    private function loadPro() {
        try {
            if (!class_exists('RFC_Pro_Loader')) {
                return;
            }
            $pro = new RFC_Pro_Loader($this->settings);
            $pro->init();
            $this->modules['pro'] = $pro;
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('RocketFuel: Failed to load Pro - ' . $e->getMessage());
            }
        }
    }

    public static function selfRepair() {
        try {
            if (class_exists('RFC_Activator')) {
                RFC_Activator::checkAndRepair();
            }
        } catch (\Throwable $e) {
            // silent
        }
    }

    public function hasPro() {
        return is_dir(RFC_PATH . 'pro/') && file_exists(RFC_PATH . 'pro/class-rfc-pro-loader.php');
    }

    public function module($key) {
        return $this->modules[$key] ?? null;
    }

    public function settings() {
        return $this->settings;
    }

    public function isReady() {
        return $this->state === 2;
    }
}
