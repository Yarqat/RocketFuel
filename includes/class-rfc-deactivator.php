<?php
defined('ABSPATH') || exit;

final class RFC_Deactivator {

    public static function run() {
        self::removeDropIn();
        self::unsetWpCache();
        self::removeHtaccess();
        self::clearSchedules();
        self::flushCache();
    }

    private static function removeDropIn() {
        $dropin = WP_CONTENT_DIR . '/advanced-cache.php';
        if (!file_exists($dropin)) {
            return;
        }
        $content = file_get_contents($dropin);
        if (strpos($content, 'RocketFuel') !== false) {
            @unlink($dropin);
        }
    }

    private static function unsetWpCache() {
        $config = ABSPATH . 'wp-config.php';
        if (!is_writable($config)) {
            return;
        }

        $content = file_get_contents($config);
        $content = preg_replace(
            '/\n?define\s*\(\s*[\'"]WP_CACHE[\'"]\s*,\s*true\s*\)\s*;\s*\n?/',
            "\n",
            $content
        );
        @file_put_contents($config, $content);
    }

    private static function removeHtaccess() {
        if (!function_exists('insert_with_markers')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }

        $htaccess = ABSPATH . '.htaccess';
        if (file_exists($htaccess) && is_writable($htaccess)) {
            insert_with_markers($htaccess, 'RocketFuel Cache', []);
        }
    }

    private static function clearSchedules() {
        $hooks = [
            'rfc_preload_event',
            'rfc_db_cleanup_event',
            'rfc_analytics_update_event',
        ];
        foreach ($hooks as $hook) {
            $ts = wp_next_scheduled($hook);
            if ($ts) {
                wp_unschedule_event($ts, $hook);
            }
        }
    }

    private static function flushCache() {
        $cache_dir = WP_CONTENT_DIR . '/cache/rocketfuel/';
        if (is_dir($cache_dir)) {
            self::recursiveDelete($cache_dir, false);
        }
    }

    private static function recursiveDelete($dir, $removeSelf = true) {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getRealPath());
            } else {
                @unlink($item->getRealPath());
            }
        }
        if ($removeSelf) {
            @rmdir($dir);
        }
    }
}
