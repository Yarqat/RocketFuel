<?php
defined('ABSPATH') || exit;

final class RFC_DNS_Prefetch {

    private $settings;
    private $domains = [];

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
        $this->collectFromEnqueued();

        $this->domains = apply_filters('rfc_dns_prefetch_domains', $this->domains);
        $this->domains = array_unique($this->domains);

        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

        foreach ($this->domains as $domain) {
            $domain = $this->normalizeDomain($domain);

            if (empty($domain)) {
                continue;
            }

            $host = wp_parse_url($domain, PHP_URL_HOST);
            if ($host === $site_host) {
                continue;
            }

            echo '<link rel="dns-prefetch" href="' . esc_attr($domain) . '">' . "\n";
        }
    }

    private function collectFromSettings() {
        $raw = $this->settings->get('dns_prefetch_urls', '');
        if (empty($raw)) {
            return;
        }

        $lines = array_filter(array_map('trim', preg_split('/[\n,]+/', $raw)));
        foreach ($lines as $line) {
            $this->domains[] = $line;
        }
    }

    private function collectFromEnqueued() {
        global $wp_scripts, $wp_styles;

        $registries = [];
        if ($wp_scripts instanceof \WP_Scripts) {
            $registries[] = $wp_scripts;
        }
        if ($wp_styles instanceof \WP_Styles) {
            $registries[] = $wp_styles;
        }

        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

        foreach ($registries as $registry) {
            foreach ($registry->registered as $dep) {
                if (empty($dep->src)) {
                    continue;
                }

                $host = wp_parse_url($dep->src, PHP_URL_HOST);
                if ($host && $host !== $site_host) {
                    $scheme = wp_parse_url($dep->src, PHP_URL_SCHEME);
                    $prefix = $scheme ? $scheme . '://' : '//';
                    $this->domains[] = $prefix . $host;
                }
            }
        }
    }

    private function normalizeDomain($input) {
        $input = trim($input);

        if (empty($input)) {
            return '';
        }

        if (strpos($input, '//') === false && strpos($input, '.') !== false) {
            return '//' . ltrim($input, '/');
        }

        $parsed = wp_parse_url($input);
        if (empty($parsed['host'])) {
            return '';
        }

        $scheme = $parsed['scheme'] ?? '';
        $prefix = !empty($scheme) ? $scheme . '://' : '//';

        return $prefix . $parsed['host'];
    }
}
