<?php
defined('ABSPATH') || exit;

final class RFC_Gzip {

    private $settings;
    private $server;
    private $cached_status;

    public function __construct(RFC_Settings $settings, RFC_Server_Detector $server) {
        $this->settings = $settings;
        $this->server = $server;
    }

    public function status() {
        if ($this->cached_status !== null) {
            return $this->cached_status;
        }

        $this->cached_status = $this->probe();
        return $this->cached_status;
    }

    private function probe() {
        $url = home_url('/');

        $response = wp_remote_get($url, [
            'headers'   => ['Accept-Encoding' => 'gzip, deflate'],
            'timeout'   => 5,
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            return $this->fallbackCheck();
        }

        $encoding = wp_remote_retrieve_header($response, 'content-encoding');
        if (!empty($encoding) && (strpos($encoding, 'gzip') !== false || strpos($encoding, 'deflate') !== false)) {
            return true;
        }

        $vary = wp_remote_retrieve_header($response, 'vary');
        if (!empty($vary) && stripos($vary, 'Accept-Encoding') !== false) {
            return true;
        }

        return $this->fallbackCheck();
    }

    private function fallbackCheck() {
        if (function_exists('apache_get_modules')) {
            $modules = apache_get_modules();
            if (in_array('mod_deflate', $modules, true)) {
                return true;
            }
        }

        if (ini_get('zlib.output_compression')) {
            return true;
        }

        if ($this->server->isApache() || $this->server->isLiteSpeed()) {
            $htaccess = ABSPATH . '.htaccess';
            if (file_exists($htaccess)) {
                $contents = @file_get_contents($htaccess);
                if ($contents !== false) {
                    if (strpos($contents, 'mod_deflate') !== false || strpos($contents, 'AddOutputFilterByType DEFLATE') !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function getApacheRules() {
        $rules = [];
        $rules[] = '<IfModule mod_deflate.c>';
        $rules[] = '    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css';
        $rules[] = '    AddOutputFilterByType DEFLATE text/javascript application/javascript application/x-javascript';
        $rules[] = '    AddOutputFilterByType DEFLATE application/json application/ld+json';
        $rules[] = '    AddOutputFilterByType DEFLATE application/xml application/xhtml+xml application/rss+xml';
        $rules[] = '    AddOutputFilterByType DEFLATE application/vnd.ms-fontobject';
        $rules[] = '    AddOutputFilterByType DEFLATE font/ttf font/otf font/opentype';
        $rules[] = '    AddOutputFilterByType DEFLATE image/svg+xml image/x-icon';
        $rules[] = '    <IfModule mod_setenvif.c>';
        $rules[] = '        BrowserMatch ^Mozilla/4 gzip-only-text/html';
        $rules[] = '        BrowserMatch ^Mozilla/4\.0[678] no-gzip';
        $rules[] = '        BrowserMatch \bMSIE !no-gzip !gzip-only-text/html';
        $rules[] = '    </IfModule>';
        $rules[] = '    <IfModule mod_headers.c>';
        $rules[] = '        Header append Vary Accept-Encoding';
        $rules[] = '    </IfModule>';
        $rules[] = '</IfModule>';

        return $rules;
    }

    public function getLiteSpeedRules() {
        $rules = [];
        $rules[] = '<IfModule LiteSpeed>';
        $rules[] = '    CacheEnable public /';
        $rules[] = '</IfModule>';
        $rules[] = '<IfModule mod_deflate.c>';
        $rules[] = '    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css';
        $rules[] = '    AddOutputFilterByType DEFLATE text/javascript application/javascript application/x-javascript';
        $rules[] = '    AddOutputFilterByType DEFLATE application/json application/ld+json';
        $rules[] = '    AddOutputFilterByType DEFLATE application/xml application/xhtml+xml application/rss+xml';
        $rules[] = '    AddOutputFilterByType DEFLATE application/vnd.ms-fontobject';
        $rules[] = '    AddOutputFilterByType DEFLATE font/ttf font/otf font/opentype';
        $rules[] = '    AddOutputFilterByType DEFLATE image/svg+xml';
        $rules[] = '</IfModule>';

        return $rules;
    }

    public function getRulesForServer() {
        if ($this->server->isLiteSpeed()) {
            return $this->getLiteSpeedRules();
        }

        if ($this->server->isApache()) {
            return $this->getApacheRules();
        }

        return [];
    }
}
