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
        $this->loadCrashGuard();
        $this->loadCore();
        $this->loadOptimization();
        $this->loadServer();
        $this->loadCleanup();
        $this->loadMedia();
        $this->loadDatabase();
        $this->loadHeartbeat();
        $this->loadCdn();
        $this->loadSecurity();
        $this->loadMonitoring();
        $this->loadSupport();
        $this->loadProGuard();
        $this->loadProInstaller();

        if (is_admin()) {
            $this->loadAdmin();
            add_action('admin_init', [__CLASS__, 'selfRepair']);
        }

        add_action('rfc_self_check', [__CLASS__, 'selfRepair']);

        if ($this->hasPro()) {
            $this->loadPro();
        }

        $this->state = 2;
        do_action('rfc_loaded');
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

    private function loadCore() {
        $cache = new RFC_Page_Cache($this->settings);
        $this->modules['page_cache'] = $cache;

        $purge = new RFC_Cache_Purge($this->settings);
        $this->modules['cache_purge'] = $purge;

        $preload = new RFC_Preload($this->settings);
        $this->modules['preload'] = $preload;
    }

    private function loadOptimization() {
        if ($this->settings->get('minify_html', true)) {
            $this->modules['minify_html'] = new RFC_Minify_HTML($this->settings);
        }

        if ($this->settings->get('minify_css', true)) {
            $this->modules['minify_css'] = new RFC_Minify_CSS($this->settings);
        }

        if ($this->settings->get('minify_js', true)) {
            $this->modules['minify_js'] = new RFC_Minify_JS($this->settings);
        }

        if ($this->settings->get('defer_js', true)) {
            $this->modules['defer_js'] = new RFC_Defer_JS($this->settings);
        }

        $this->modules['lazy_load'] = new RFC_Lazy_Load($this->settings);
        $this->modules['dns_prefetch'] = new RFC_DNS_Prefetch($this->settings);
        $this->modules['preconnect'] = new RFC_Preconnect($this->settings);
        $this->modules['font_optimize'] = new RFC_Font_Optimization($this->settings);
    }

    private function loadServer() {
        $this->modules['server'] = new RFC_Server_Detector();
        $this->modules['gzip'] = new RFC_Gzip($this->settings, $this->modules['server']);
        $this->modules['htaccess'] = new RFC_Htaccess($this->settings, $this->modules['server']);
    }

    private function loadCleanup() {
        $this->modules['cleanup'] = new RFC_Cleanup($this->settings);
    }

    private function loadMedia() {
        $this->modules['image_dimensions'] = new RFC_Image_Dimensions($this->settings);
    }

    private function loadDatabase() {
        $this->modules['db_optimizer'] = new RFC_DB_Optimizer($this->settings);
    }

    private function loadHeartbeat() {
        $this->modules['heartbeat'] = new RFC_Heartbeat_Control($this->settings);
    }

    private function loadCdn() {
        if ($this->settings->get('cdn_enabled', false)) {
            $this->modules['cdn'] = new RFC_CDN($this->settings);
        }
    }

    private function loadSecurity() {
        $this->modules['security'] = new RFC_Security($this->settings);
    }

    private function loadCrashGuard() {
        $this->modules['crash_guard'] = new RFC_Crash_Guard($this->settings);
    }

    private function loadMonitoring() {
        $this->modules['speed_test'] = new RFC_Speed_Test($this->settings);
        $this->modules['suggestions'] = new RFC_Suggestions($this->settings);
    }

    private function loadSupport() {
        $this->modules['support'] = new RFC_Support($this->settings);
    }

    private function loadProGuard() {
        $this->modules['pro_guard'] = new RFC_Pro_Guard($this->settings);
    }

    private function loadProInstaller() {
        $this->modules['pro_installer'] = new RFC_Pro_Installer();
    }

    private function loadAdmin() {
        $this->modules['admin'] = new RFC_Admin($this->settings);
        $this->modules['admin_bar'] = new RFC_Admin_Bar($this->settings);
        $this->modules['notices'] = new RFC_Notices($this->settings);

        new RFC_Conflict_Detector($this->settings);
    }

    private function loadPro() {
        if (!class_exists('RFC_Pro_Loader')) {
            return;
        }
        $pro = new RFC_Pro_Loader($this->settings);
        $pro->init();
        $this->modules['pro'] = $pro;
    }

    public static function selfRepair() {
        RFC_Activator::checkAndRepair();
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
