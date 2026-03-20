<?php
defined('ABSPATH') || exit;

final class RFC_Helpers {

    public static function getCacheSize() {
        $dir = RFC_CACHE_DIR;
        if (!is_dir($dir)) {
            return 0;
        }
        $size = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Throwable $e) {
            return 0;
        }
        return $size;
    }

    public static function getCachedPageCount() {
        $dir = RFC_CACHE_DIR;
        if (!is_dir($dir)) {
            return 0;
        }
        $count = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile() && preg_match('/index-(https?|http)\.html$/', $file->getFilename())) {
                    $count++;
                }
            }
        } catch (\Throwable $e) {
            return 0;
        }
        return $count;
    }

    public static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function getLastCleared() {
        $time = get_option('rfc_last_cache_clear', 0);
        if (!$time) {
            return 'Never';
        }
        return human_time_diff($time, time()) . ' ago';
    }
}

function rfc_get_cache_size() {
    return RFC_Helpers::formatBytes(RFC_Helpers::getCacheSize());
}

function rfc_get_cached_page_count() {
    return RFC_Helpers::getCachedPageCount();
}

function rfc_format_bytes($bytes) {
    return RFC_Helpers::formatBytes($bytes);
}

function rfc_last_cleared() {
    return RFC_Helpers::getLastCleared();
}
