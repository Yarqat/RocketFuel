<?php
defined('ABSPATH') || exit;

final class RFC_Htaccess {

    private $settings;
    private $server;
    private $marker = 'RocketFuel Cache';
    private $path;

    public function __construct(RFC_Settings $settings, RFC_Server_Detector $server) {
        $this->settings = $settings;
        $this->server = $server;
        $this->path = $this->resolveHtaccessPath();
    }

    private function resolveHtaccessPath() {
        $path = ABSPATH . '.htaccess';
        return file_exists($path) || $this->server->isWritable(ABSPATH) ? $path : null;
    }

    public function writeRules() {
        if (!$this->canOperate()) {
            return false;
        }

        $rules = $this->buildRules();
        if (empty($rules)) {
            return false;
        }

        return insert_with_markers($this->path, $this->marker, $rules);
    }

    public function removeRules() {
        if (!$this->canOperate()) {
            return false;
        }

        return insert_with_markers($this->path, $this->marker, []);
    }

    public function hasRules() {
        if (!$this->canOperate() || !file_exists($this->path)) {
            return false;
        }

        $contents = @file_get_contents($this->path);
        if ($contents === false) {
            return false;
        }

        $start = "# BEGIN {$this->marker}";
        $end = "# END {$this->marker}";

        return strpos($contents, $start) !== false && strpos($contents, $end) !== false;
    }

    public function getRules() {
        if (!$this->canOperate() || !file_exists($this->path)) {
            return '';
        }

        $contents = @file_get_contents($this->path);
        if ($contents === false) {
            return '';
        }

        $start = "# BEGIN {$this->marker}";
        $end = "# END {$this->marker}";

        $start_pos = strpos($contents, $start);
        $end_pos = strpos($contents, $end);

        if ($start_pos === false || $end_pos === false) {
            return '';
        }

        return substr($contents, $start_pos, $end_pos + strlen($end) - $start_pos);
    }

    private function buildRules() {
        $lines = [];

        $lines[] = '<IfModule mod_expires.c>';
        $lines[] = '    ExpiresActive On';
        $lines[] = '    ExpiresByType text/html "access plus 0 seconds"';
        $lines[] = '    ExpiresByType text/css "access plus 1 year"';
        $lines[] = '    ExpiresByType text/javascript "access plus 1 year"';
        $lines[] = '    ExpiresByType application/javascript "access plus 1 year"';
        $lines[] = '    ExpiresByType application/x-javascript "access plus 1 year"';
        $lines[] = '    ExpiresByType image/jpeg "access plus 1 year"';
        $lines[] = '    ExpiresByType image/png "access plus 1 year"';
        $lines[] = '    ExpiresByType image/gif "access plus 1 year"';
        $lines[] = '    ExpiresByType image/webp "access plus 1 year"';
        $lines[] = '    ExpiresByType image/avif "access plus 1 year"';
        $lines[] = '    ExpiresByType image/svg+xml "access plus 1 year"';
        $lines[] = '    ExpiresByType image/x-icon "access plus 1 year"';
        $lines[] = '    ExpiresByType font/woff2 "access plus 1 year"';
        $lines[] = '    ExpiresByType font/woff "access plus 1 year"';
        $lines[] = '    ExpiresByType font/ttf "access plus 1 year"';
        $lines[] = '    ExpiresByType font/otf "access plus 1 year"';
        $lines[] = '    ExpiresByType application/vnd.ms-fontobject "access plus 1 year"';
        $lines[] = '    ExpiresByType application/pdf "access plus 1 month"';
        $lines[] = '    ExpiresByType video/mp4 "access plus 1 year"';
        $lines[] = '    ExpiresByType video/webm "access plus 1 year"';
        $lines[] = '    ExpiresDefault "access plus 1 month"';
        $lines[] = '</IfModule>';
        $lines[] = '';

        $gzip = new RFC_Gzip($this->settings, $this->server);
        $gzipRules = $gzip->getRulesForServer();
        if (!empty($gzipRules)) {
            $lines = array_merge($lines, $gzipRules);
            $lines[] = '';
        }

        $lines[] = '<IfModule mod_headers.c>';
        $lines[] = '    Header set X-Powered-By "RocketFuel Cache"';
        $lines[] = '    <FilesMatch "\.(js|css|gif|png|jpe?g|webp|avif|ico|svg|woff2?|ttf|otf|eot)$">';
        $lines[] = '        Header unset ETag';
        $lines[] = '        Header set Cache-Control "public, max-age=31536000, immutable"';
        $lines[] = '    </FilesMatch>';
        $lines[] = '    <FilesMatch "\.(html|htm)$">';
        $lines[] = '        Header set Cache-Control "no-cache, must-revalidate, max-age=0"';
        $lines[] = '    </FilesMatch>';
        $lines[] = '</IfModule>';
        $lines[] = '';

        $lines[] = 'FileETag None';
        $lines[] = '';

        if ($this->settings->get('page_cache_enabled', true)) {
            $lines[] = '<IfModule mod_rewrite.c>';
            $lines[] = '    RewriteEngine On';
            $lines[] = '    RewriteCond %{REQUEST_METHOD} GET';
            $lines[] = '    RewriteCond %{QUERY_STRING} ^$';
            $lines[] = '    RewriteCond %{HTTP_COOKIE} !wordpress_logged_in_';
            $lines[] = '    RewriteCond %{HTTP_COOKIE} !woocommerce_items_in_cart';
            $lines[] = '    RewriteCond %{HTTP_COOKIE} !comment_author_';

            $scheme_prefix = '%{ENV:RFC_SCHEME}';
            $lines[] = '    RewriteRule .* - [E=RFC_SCHEME:http]';
            $lines[] = '    RewriteCond %{HTTPS} on [OR]';
            $lines[] = '    RewriteCond %{SERVER_PORT} ^443$';
            $lines[] = '    RewriteRule .* - [E=RFC_SCHEME:https]';

            $cache_base = str_replace(ABSPATH, '', WP_CONTENT_DIR) . '/cache/rocketfuel/';
            $lines[] = "    RewriteCond %{{DOCUMENT_ROOT}}/{$cache_base}%{HTTP_HOST}/%{REQUEST_URI}/index-{$scheme_prefix}.html -f";
            $lines[] = "    RewriteRule ^(.*)$ /{$cache_base}%{HTTP_HOST}/%{REQUEST_URI}/index-{$scheme_prefix}.html [L]";
            $lines[] = '</IfModule>';
        }

        return $lines;
    }

    private function canOperate() {
        if ($this->path === null) {
            return false;
        }

        if (!$this->server->isApache() && !$this->server->isLiteSpeed()) {
            return false;
        }

        return true;
    }

    public function getPath() {
        return $this->path;
    }

    public function isAvailable() {
        return $this->canOperate();
    }
}
