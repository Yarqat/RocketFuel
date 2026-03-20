<?php
defined('ABSPATH') || exit;

final class RFC_Page_Cache {

    private $settings;
    private $started = false;
    private $cacheable = true;
    private $start_time;

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        if ($this->settings->isSafeMode()) {
            return;
        }

        if (!$this->settings->get('page_cache_enabled', true)) {
            return;
        }

        add_action('template_redirect', [$this, 'intercept'], -999);
        add_action('shutdown', [$this, 'finalize'], 0);
    }

    public function intercept() {
        if (!$this->shouldProcess()) {
            $this->cacheable = false;
            return;
        }

        $cached = $this->serveCached();
        if ($cached) {
            exit;
        }

        $this->start_time = microtime(true);
        $this->started = true;
        ob_start([$this, 'capture']);
    }

    public function capture($buffer) {
        if (!$this->cacheable || strlen($buffer) < 255) {
            return $buffer;
        }

        if (http_response_code() !== 200) {
            return $buffer;
        }

        if (strpos($buffer, '</html>') === false) {
            return $buffer;
        }

        $elapsed = round((microtime(true) - $this->start_time) * 1000, 2);
        $tag = "\n<!-- Cached by RocketFuel | " . gmdate('Y-m-d H:i:s') . " UTC | {$elapsed}ms -->";
        $buffer .= $tag;

        $this->writeCacheFile($buffer);

        return $buffer;
    }

    public function finalize() {
        if ($this->started && ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    private function serveCached() {
        $path = $this->resolvePath();
        if (!$path || !file_exists($path)) {
            return false;
        }

        $ttl = (int) $this->settings->get('cache_lifespan', 36000);
        if ($ttl > 0 && (time() - filemtime($path)) > $ttl) {
            @unlink($path);
            $gz = $path . '.gz';
            if (file_exists($gz)) {
                @unlink($gz);
            }
            return false;
        }

        $accept = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        $gz = $path . '.gz';

        header('X-RocketFuel-Cache: HIT');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT');

        if (strpos($accept, 'gzip') !== false && file_exists($gz)) {
            header('Content-Encoding: gzip');
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Length: ' . filesize($gz));
            readfile($gz);
            return true;
        }

        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        return true;
    }

    private function writeCacheFile($html) {
        $path = $this->resolvePath();
        if (!$path) {
            return;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        @file_put_contents($path, $html);
        @file_put_contents($path . '.gz', gzencode($html, 6));

        do_action('rfc_cache_created', $this->currentUrl(), $path);
    }

    private function resolvePath() {
        $url = $this->currentUrl();
        $parsed = wp_parse_url($url);

        if (!$parsed || empty($parsed['host'])) {
            return false;
        }

        $host = sanitize_file_name($parsed['host']);
        $uri = trim($parsed['path'] ?? '/', '/');
        $uri = $uri === '' ? 'index' : $uri;

        $parts = array_map('sanitize_file_name', explode('/', $uri));
        $subpath = implode('/', $parts);

        $scheme = is_ssl() ? 'https' : 'http';
        $filename = "index-{$scheme}.html";

        return RFC_CACHE_DIR . $host . '/' . $subpath . '/' . $filename;
    }

    private function currentUrl() {
        $scheme = is_ssl() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $strip = array_filter(array_map('trim', explode(',', $this->settings->get('strip_query_strings', ''))));
        if (!empty($strip) && strpos($uri, '?') !== false) {
            $parts = explode('?', $uri, 2);
            parse_str($parts[1], $params);
            foreach ($strip as $key) {
                unset($params[$key]);
            }
            $uri = $parts[0];
            if (!empty($params)) {
                $uri .= '?' . http_build_query($params);
            }
        }

        return $scheme . '://' . $host . $uri;
    }

    private function shouldProcess() {
        if (defined('DOING_AJAX') && DOING_AJAX) return false;
        if (defined('DOING_CRON') && DOING_CRON) return false;
        if (defined('REST_REQUEST') && REST_REQUEST) return false;
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) return false;
        if (defined('WP_CLI') && WP_CLI) return false;

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') return false;
        if (is_user_logged_in() && !$this->settings->get('logged_in_cache', false)) return false;
        if (is_admin()) return false;
        if (is_search()) return false;
        if (is_404()) return false;
        if (is_preview()) return false;

        if (!empty($_POST)) return false;

        if (headers_sent()) return false;

        if ($this->isExcludedUrl()) return false;
        if ($this->hasExcludedCookie()) return false;
        if ($this->hasExcludedAgent()) return false;
        if ($this->hasBlockingQueryString()) return false;

        if (function_exists('is_cart') && is_cart()) return false;
        if (function_exists('is_checkout') && is_checkout()) return false;
        if (function_exists('is_account_page') && is_account_page()) return false;

        return apply_filters('rfc_is_cacheable', true, $this->currentUrl());
    }

    private function isExcludedUrl() {
        $exclusions = $this->settings->get('never_cache_urls', '');
        if (empty($exclusions)) {
            return false;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $lines = array_filter(array_map('trim', explode("\n", $exclusions)));

        foreach ($lines as $pattern) {
            if (empty($pattern)) continue;

            if ($pattern[0] === '/' && substr($pattern, -1) === '/' && @preg_match($pattern, '') !== false) {
                if (preg_match($pattern, $uri)) {
                    return true;
                }
                continue;
            }

            $regex = str_replace(['.', '*', '?'], ['\.', '.*', '.'], $pattern);
            if (preg_match('#' . $regex . '#i', $uri)) {
                return true;
            }
        }

        return false;
    }

    private function hasExcludedCookie() {
        $exclusions = $this->settings->get('never_cache_cookies', '');
        if (empty($exclusions) || empty($_COOKIE)) {
            return false;
        }

        $patterns = array_filter(array_map('trim', explode(',', $exclusions)));
        $cookie_keys = array_keys($_COOKIE);

        foreach ($patterns as $pattern) {
            foreach ($cookie_keys as $key) {
                if (fnmatch($pattern, $key)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasExcludedAgent() {
        $exclusions = $this->settings->get('never_cache_user_agents', '');
        if (empty($exclusions)) {
            return false;
        }

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $agents = array_filter(array_map('trim', explode("\n", $exclusions)));

        foreach ($agents as $agent) {
            if (stripos($ua, $agent) !== false) {
                return true;
            }
        }

        return false;
    }

    private function hasBlockingQueryString() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '?') === false) {
            return false;
        }

        $allowed = $this->settings->get('cache_query_strings', '');
        if (empty($allowed)) {
            return strpos($uri, '?') !== false && !empty($_GET);
        }

        $allowed_keys = array_filter(array_map('trim', explode(',', $allowed)));
        $query_keys = array_keys($_GET);

        foreach ($query_keys as $key) {
            if (!in_array($key, $allowed_keys, true)) {
                return true;
            }
        }

        return false;
    }

    public static function flush($url = null) {
        if ($url !== null) {
            $instance = new self(RFC_Settings::instance());
            $path = $instance->resolvePath();
            if ($path && file_exists($path)) {
                @unlink($path);
                @unlink($path . '.gz');
                do_action('rfc_cache_cleared_url', $url);
            }
            return;
        }

        $dir = RFC_CACHE_DIR;
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        $count = 0;
        foreach ($iterator as $item) {
            if ($item->isFile() && preg_match('/\.(html|gz|br)$/', $item->getFilename())) {
                @unlink($item->getRealPath());
                $count++;
            }
        }

        do_action('rfc_cache_cleared_all');
    }
}
