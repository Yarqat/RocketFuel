<?php
defined('ABSPATH') || exit;

final class RFC_Server_Detector {

    private $type;
    private $software;

    public function __construct() {
        $this->software = isset($_SERVER['SERVER_SOFTWARE']) ? strtolower($_SERVER['SERVER_SOFTWARE']) : '';
        $this->type = $this->detect();
    }

    private function detect() {
        if (strpos($this->software, 'litespeed') !== false) {
            return 'litespeed';
        }

        if (strpos($this->software, 'apache') !== false) {
            return 'apache';
        }

        if (strpos($this->software, 'nginx') !== false) {
            return 'nginx';
        }

        if (strpos($this->software, 'microsoft-iis') !== false || strpos($this->software, 'iis') !== false) {
            return 'iis';
        }

        if (defined('LITESPEED_SERVER_TYPE') || isset($_SERVER['X-LSCACHE'])) {
            return 'litespeed';
        }

        if (is_file('/etc/nginx/nginx.conf') || is_dir('/etc/nginx')) {
            return 'nginx';
        }

        return 'unknown';
    }

    public function getType() {
        return $this->type;
    }

    public function isApache() {
        return $this->type === 'apache';
    }

    public function isNginx() {
        return $this->type === 'nginx';
    }

    public function isLiteSpeed() {
        return $this->type === 'litespeed';
    }

    public function getPhpVersion() {
        return PHP_VERSION;
    }

    public function hasOpcache() {
        return function_exists('opcache_get_status') && !empty(ini_get('opcache.enable'));
    }

    public function getOpcacheStats() {
        if (!$this->hasOpcache()) {
            return [];
        }

        $status = @opcache_get_status(false);
        if (!is_array($status)) {
            return [];
        }

        $mem = $status['memory_usage'] ?? [];
        $stats = $status['opcache_statistics'] ?? [];

        return [
            'enabled'       => $status['opcache_enabled'] ?? false,
            'used_memory'   => $mem['used_memory'] ?? 0,
            'free_memory'   => $mem['free_memory'] ?? 0,
            'wasted_memory' => $mem['wasted_memory'] ?? 0,
            'hit_rate'      => $stats['opcache_hit_rate'] ?? 0,
            'cached_scripts' => $stats['num_cached_scripts'] ?? 0,
            'misses'        => $stats['misses'] ?? 0,
        ];
    }

    public function hasGd() {
        return extension_loaded('gd') && function_exists('gd_info');
    }

    public function hasImagick() {
        return extension_loaded('imagick') && class_exists('Imagick');
    }

    public function getMemoryLimit() {
        $limit = ini_get('memory_limit');
        if (empty($limit) || $limit === '-1') {
            return -1;
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;

        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }

    public function hasGzip() {
        if (function_exists('ob_gzhandler') || extension_loaded('zlib')) {
            return true;
        }

        if ($this->isApache() || $this->isLiteSpeed()) {
            return function_exists('apache_get_modules') && in_array('mod_deflate', apache_get_modules(), true);
        }

        return false;
    }

    public function hasBrotli() {
        if (extension_loaded('brotli') || function_exists('brotli_compress')) {
            return true;
        }

        if ($this->isApache() && function_exists('apache_get_modules')) {
            return in_array('mod_brotli', apache_get_modules(), true);
        }

        return false;
    }

    public function isHttps() {
        if (is_ssl()) {
            return true;
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        if (isset($_SERVER['HTTP_CF_VISITOR'])) {
            $visitor = json_decode($_SERVER['HTTP_CF_VISITOR'], true);
            if (isset($visitor['scheme']) && $visitor['scheme'] === 'https') {
                return true;
            }
        }

        return false;
    }

    public function hasHttp2() {
        if (isset($_SERVER['SERVER_PROTOCOL']) && strpos($_SERVER['SERVER_PROTOCOL'], '2.0') !== false) {
            return true;
        }

        if (isset($_SERVER['HTTP2']) && $_SERVER['HTTP2'] === 'on') {
            return true;
        }

        if (isset($_SERVER['H2']) || isset($_SERVER['H2C'])) {
            return true;
        }

        return false;
    }

    public function isWritable($path) {
        if (empty($path)) {
            return false;
        }

        if (is_dir($path)) {
            return wp_is_writable($path);
        }

        if (file_exists($path)) {
            return wp_is_writable($path);
        }

        $parent = dirname($path);
        return is_dir($parent) && wp_is_writable($parent);
    }

    public function raw() {
        return $this->software;
    }

    public function toArray() {
        return [
            'type'         => $this->type,
            'software'     => $this->software,
            'php_version'  => $this->getPhpVersion(),
            'memory_limit' => $this->getMemoryLimit(),
            'opcache'      => $this->hasOpcache(),
            'gd'           => $this->hasGd(),
            'imagick'      => $this->hasImagick(),
            'gzip'         => $this->hasGzip(),
            'brotli'       => $this->hasBrotli(),
            'https'        => $this->isHttps(),
            'http2'        => $this->hasHttp2(),
        ];
    }
}
