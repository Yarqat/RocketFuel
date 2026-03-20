<?php
defined('ABSPATH') || exit;

final class RFC_Preconnect {

    private $settings;
    private $urls = [];

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        if ($this->settings->isSafeMode()) {
            return;
        }

        add_action('wp_head', [$this, 'outputTags'], 1);
    }

    public function outputTags() {
        if (is_admin()) {
            return;
        }

        $this->collectFromSettings();

        $this->urls = apply_filters('rfc_preconnect_urls', $this->urls);
        $this->urls = array_unique(array_map([$this, 'normalizeOrigin'], $this->urls));

        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

        foreach ($this->urls as $origin) {
            if (empty($origin)) {
                continue;
            }

            $host = wp_parse_url($origin, PHP_URL_HOST);
            if ($host === $site_host) {
                continue;
            }

            $crossorigin = $this->needsCrossOrigin($origin) ? ' crossorigin' : '';
            echo '<link rel="preconnect" href="' . esc_attr($origin) . '"' . $crossorigin . '>' . "\n";
        }
    }

    private function collectFromSettings() {
        $raw = $this->settings->get('preconnect_urls', '');
        if (empty($raw)) {
            return;
        }

        $lines = array_filter(array_map('trim', preg_split('/[\n,]+/', $raw)));
        foreach ($lines as $line) {
            $this->urls[] = $line;
        }
    }

    private function normalizeOrigin($input) {
        $input = trim($input);
        if (empty($input)) {
            return '';
        }

        if (strpos($input, '//') === false && strpos($input, '.') !== false) {
            return 'https://' . ltrim($input, '/');
        }

        $parsed = wp_parse_url($input);
        if (empty($parsed['host'])) {
            return '';
        }

        $scheme = !empty($parsed['scheme']) ? $parsed['scheme'] : 'https';
        return $scheme . '://' . $parsed['host'];
    }

    private function needsCrossOrigin($origin) {
        $font_domains = [
            'fonts.googleapis.com',
            'fonts.gstatic.com',
            'use.typekit.net',
            'use.fontawesome.com',
            'cdnjs.cloudflare.com',
        ];

        $host = wp_parse_url($origin, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        foreach ($font_domains as $fd) {
            if ($host === $fd || strpos($host, $fd) !== false) {
                return true;
            }
        }

        return false;
    }
}
