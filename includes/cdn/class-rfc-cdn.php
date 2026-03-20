<?php
defined('ABSPATH') || exit;

final class RFC_CDN {

    private $settings;
    private $cdnUrl;
    private $siteUrl;
    private $directories = [];
    private $excludedExtensions = [];
    private $excludedUrls = [];

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        $this->cdnUrl = rtrim($this->settings->get('cdn_url', ''), '/');
        if (empty($this->cdnUrl)) {
            return;
        }

        $this->siteUrl = rtrim(site_url(), '/');
        $this->parseConfig();

        add_filter('the_content', [$this, 'rewriteContent'], 9999);
        add_filter('script_loader_src', [$this, 'rewriteAssetUrl'], 9999);
        add_filter('style_loader_src', [$this, 'rewriteAssetUrl'], 9999);
        add_filter('wp_get_attachment_url', [$this, 'rewriteAssetUrl'], 9999);
    }

    private function parseConfig() {
        $dirs = $this->settings->get('cdn_directories', 'wp-content,wp-includes');
        $this->directories = array_filter(array_map('trim', explode(',', $dirs)));

        $exts = $this->settings->get('cdn_excluded_extensions', '.php');
        $this->excludedExtensions = array_filter(array_map(function ($ext) {
            $ext = trim($ext);
            return strpos($ext, '.') === 0 ? $ext : '.' . $ext;
        }, explode(',', $exts)));

        $urls = $this->settings->get('cdn_excluded_urls', '');
        $this->excludedUrls = array_filter(array_map('trim', explode("\n", $urls)));
    }

    public function rewriteContent($content) {
        if (empty($content) || is_admin()) {
            return $content;
        }

        $pattern = $this->buildPattern();
        if ($pattern === false) {
            return $content;
        }

        return preg_replace_callback($pattern, [$this, 'replaceMatch'], $content);
    }

    public function rewriteAssetUrl($url) {
        if (empty($url) || is_admin()) {
            return $url;
        }

        if ($this->isExcluded($url)) {
            return $url;
        }

        if (!$this->isRewritable($url)) {
            return $url;
        }

        return str_replace($this->siteUrl, $this->cdnUrl, $url);
    }

    private function buildPattern() {
        $escaped = preg_quote($this->siteUrl, '#');
        $dirs = array_map('preg_quote', $this->directories);

        if (empty($dirs)) {
            return false;
        }

        $dirGroup = implode('|', $dirs);

        return '#(?:' . $escaped . ')\s*/\s*(' . $dirGroup . ')/([^\s\'"<>]+)#i';
    }

    private function replaceMatch($matches) {
        $full = $matches[0];

        if ($this->isExcluded($full)) {
            return $full;
        }

        return str_replace($this->siteUrl, $this->cdnUrl, $full);
    }

    private function isRewritable($url) {
        if (strpos($url, $this->siteUrl) === false) {
            return false;
        }

        if (empty($this->directories)) {
            return true;
        }

        foreach ($this->directories as $dir) {
            if (strpos($url, '/' . $dir . '/') !== false) {
                return true;
            }
        }

        return false;
    }

    private function isExcluded($url) {
        foreach ($this->excludedExtensions as $ext) {
            $path = strtok($url, '?');
            if (substr($path, -strlen($ext)) === $ext) {
                return true;
            }
        }

        foreach ($this->excludedUrls as $excluded) {
            if (empty($excluded)) {
                continue;
            }
            if (strpos($url, $excluded) !== false) {
                return true;
            }
        }

        return false;
    }
}
