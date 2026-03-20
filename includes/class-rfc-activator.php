<?php
defined('ABSPATH') || exit;

final class RFC_Activator {

    public static function run() {
        self::createCacheStructure();
        self::installDropIn();
        self::setWpCache();
        self::writeHtaccess();
        self::scheduleEvents();
        self::setDefaults();
        self::detectConflicts();
        update_option('rfc_needs_setup', false);
        flush_rewrite_rules();
    }

    public static function checkAndRepair() {
        $needs_repair = false;

        if (!defined('WP_CACHE') || !WP_CACHE) {
            self::setWpCache();
            $needs_repair = true;
        }

        $dropin = WP_CONTENT_DIR . '/advanced-cache.php';
        if (!file_exists($dropin)) {
            self::installDropIn();
            $needs_repair = true;
        } else {
            $content = file_get_contents($dropin);
            if (strpos($content, 'RocketFuel') === false) {
                self::installDropIn();
                $needs_repair = true;
            }
        }

        if (!is_dir(RFC_CACHE_DIR)) {
            self::createCacheStructure();
            $needs_repair = true;
        }

        if ($needs_repair) {
            update_option('rfc_last_repair', time());
        }

        return !$needs_repair;
    }

    private static function createCacheStructure() {
        $dirs = [
            WP_CONTENT_DIR . '/cache/rocketfuel/',
            WP_CONTENT_DIR . '/cache/rocketfuel/min/css/',
            WP_CONTENT_DIR . '/cache/rocketfuel/min/js/',
            WP_CONTENT_DIR . '/cache/rocketfuel/fonts/',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
            $index = $dir . 'index.html';
            if (!file_exists($index)) {
                @file_put_contents($index, '');
            }
        }
    }

    private static function installDropIn() {
        $target = WP_CONTENT_DIR . '/advanced-cache.php';
        $source = RFC_PATH . 'advanced-cache-template.php';

        if (!file_exists($source)) {
            return;
        }

        if (file_exists($target)) {
            $content = file_get_contents($target);
            if (strpos($content, 'RocketFuel') === false) {
                if (!is_writable($target)) {
                    @chmod($target, 0644);
                }
                if (!is_writable($target)) {
                    self::tryFilesystemWrite($target, self::buildDropIn($source));
                    return;
                }
            }
        }

        $dropin_content = self::buildDropIn($source);

        if (@file_put_contents($target, $dropin_content) === false) {
            self::tryFilesystemWrite($target, $dropin_content);
        }

        delete_option('rfc_foreign_dropin');
    }

    private static function buildDropIn($source) {
        $template = file_get_contents($source);
        $template = str_replace('{{RFC_PATH}}', RFC_PATH, $template);
        $template = str_replace('{{RFC_CACHE_DIR}}', RFC_CACHE_DIR, $template);
        return $template;
    }

    private static function tryFilesystemWrite($path, $content) {
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $creds = request_filesystem_credentials('', '', false, dirname($path));
        if ($creds === false) {
            return false;
        }

        WP_Filesystem($creds);
        global $wp_filesystem;

        if (!$wp_filesystem) {
            return false;
        }

        return $wp_filesystem->put_contents($path, $content, FS_CHMOD_FILE);
    }

    private static function setWpCache() {
        if (defined('WP_CACHE') && WP_CACHE === true) {
            delete_option('rfc_wpconfig_readonly');
            return;
        }

        $config = ABSPATH . 'wp-config.php';

        if (!file_exists($config)) {
            $config = dirname(ABSPATH) . '/wp-config.php';
        }

        if (!file_exists($config)) {
            update_option('rfc_wpconfig_readonly', true);
            return;
        }

        if (!is_writable($config)) {
            @chmod($config, 0644);
        }

        if (!is_writable($config)) {
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            WP_Filesystem();
            global $wp_filesystem;

            if ($wp_filesystem) {
                $content = $wp_filesystem->get_contents($config);
                if ($content && strpos($content, "'WP_CACHE'") === false && strpos($content, '"WP_CACHE"') === false) {
                    $content = self::injectWpCache($content);
                    $wp_filesystem->put_contents($config, $content, FS_CHMOD_FILE);
                    delete_option('rfc_wpconfig_readonly');
                    return;
                }
            }

            update_option('rfc_wpconfig_readonly', true);
            return;
        }

        $content = file_get_contents($config);

        if (preg_match('/define\s*\(\s*[\'"]WP_CACHE[\'"]\s*,\s*true/', $content)) {
            delete_option('rfc_wpconfig_readonly');
            return;
        }

        if (preg_match('/define\s*\(\s*[\'"]WP_CACHE[\'"]\s*,\s*false\s*\)/', $content)) {
            $content = preg_replace(
                '/define\s*\(\s*[\'"]WP_CACHE[\'"]\s*,\s*false\s*\)/',
                "define('WP_CACHE', true)",
                $content
            );
            @file_put_contents($config, $content);
            delete_option('rfc_wpconfig_readonly');
            return;
        }

        if (strpos($content, "'WP_CACHE'") === false && strpos($content, '"WP_CACHE"') === false) {
            $content = self::injectWpCache($content);
            @file_put_contents($config, $content);
            delete_option('rfc_wpconfig_readonly');
        }
    }

    private static function injectWpCache($content) {
        $anchors = [
            "/* That's all, stop editing!",
            "/* That's all, stop editing",
            "/** Absolute path to the WordPress directory",
            "require_once ABSPATH",
            "require_once(ABSPATH",
        ];

        foreach ($anchors as $anchor) {
            if (strpos($content, $anchor) !== false) {
                $content = str_replace(
                    $anchor,
                    "define('WP_CACHE', true); // Added by RocketFuel Cache\n\n" . $anchor,
                    $content
                );
                return $content;
            }
        }

        $content = preg_replace(
            '/^<\?php\s*/i',
            "<?php\ndefine('WP_CACHE', true); // Added by RocketFuel Cache\n",
            $content,
            1
        );

        return $content;
    }

    private static function writeHtaccess() {
        if (!function_exists('insert_with_markers')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }

        $server = strtolower($_SERVER['SERVER_SOFTWARE'] ?? '');
        if (strpos($server, 'nginx') !== false) {
            return;
        }

        $htaccess = ABSPATH . '.htaccess';
        if (!file_exists($htaccess)) {
            return;
        }

        if (!is_writable($htaccess)) {
            @chmod($htaccess, 0644);
        }

        if (!is_writable($htaccess)) {
            return;
        }

        $rules = self::generateHtaccessRules();
        insert_with_markers($htaccess, 'RocketFuel Cache', explode("\n", $rules));
    }

    private static function generateHtaccessRules() {
        $rules = '';
        $rules .= "<IfModule mod_deflate.c>\n";
        $rules .= "  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json application/xml image/svg+xml\n";
        $rules .= "</IfModule>\n\n";

        $rules .= "<IfModule mod_expires.c>\n";
        $rules .= "  ExpiresActive On\n";
        $rules .= "  ExpiresByType text/css \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType application/javascript \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType image/jpeg \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType image/png \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType image/gif \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType image/webp \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType image/avif \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType image/svg+xml \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType font/woff2 \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType font/woff \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType application/font-woff2 \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType video/mp4 \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType application/pdf \"access plus 1 month\"\n";
        $rules .= "  ExpiresByType text/html \"access plus 0 seconds\"\n";
        $rules .= "</IfModule>\n\n";

        $rules .= "<IfModule mod_headers.c>\n";
        $rules .= "  <FilesMatch \"\\.(css|js|gif|jpe?g|png|webp|avif|svg|woff2?|ttf|eot|ico)$\">\n";
        $rules .= "    Header set Cache-Control \"public, max-age=31536000, immutable\"\n";
        $rules .= "  </FilesMatch>\n";
        $rules .= "  Header set X-Powered-By \"RocketFuel\"\n";
        $rules .= "</IfModule>\n";

        return $rules;
    }

    private static function scheduleEvents() {
        if (!wp_next_scheduled('rfc_preload_event')) {
            wp_schedule_event(time() + 300, 'hourly', 'rfc_preload_event');
        }

        if (!wp_next_scheduled('rfc_self_check')) {
            wp_schedule_event(time() + 600, 'twicedaily', 'rfc_self_check');
        }
    }

    private static function setDefaults() {
        if (get_option('rfc_settings') === false) {
            add_option('rfc_settings', [], '', true);
        }
        update_option('rfc_activated_at', time());
        update_option('rfc_version', RFC_VERSION);
    }

    private static function detectConflicts() {
        $known = [
            'wp-rocket/wp-rocket.php'                => 'WP Rocket',
            'w3-total-cache/w3-total-cache.php'      => 'W3 Total Cache',
            'wp-super-cache/wp-cache.php'            => 'WP Super Cache',
            'litespeed-cache/litespeed-cache.php'    => 'LiteSpeed Cache',
            'wp-fastest-cache/wpFastestCache.php'    => 'WP Fastest Cache',
            'autoptimize/autoptimize.php'            => 'Autoptimize',
            'wp-optimize/wp-optimize.php'            => 'WP-Optimize',
            'sg-cachepress/sg-cachepress.php'        => 'SG Optimizer',
            'breeze/breeze.php'                      => 'Breeze',
            'hummingbird-performance/wp-hummingbird.php' => 'Hummingbird',
        ];

        $active = get_option('active_plugins', []);
        $conflicts = [];

        foreach ($known as $plugin => $name) {
            if (in_array($plugin, $active, true)) {
                $conflicts[$plugin] = $name;
            }
        }

        if (!empty($conflicts)) {
            update_option('rfc_conflicts', $conflicts);
        } else {
            delete_option('rfc_conflicts');
        }
    }
}
