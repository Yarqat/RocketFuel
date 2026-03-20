<?php
defined('ABSPATH') || exit;

final class RFC_Image_Dimensions {

    private $settings;
    private $uploadDir;
    private $uploadUrl;
    private $transientPrefix = 'rfc_imgdim_';

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        if (!$this->settings->get('add_missing_dimensions', true)) {
            return;
        }

        if ($this->settings->isSafeMode()) {
            return;
        }

        $upload = wp_upload_dir(null, false);
        $this->uploadDir = $upload['basedir'];
        $this->uploadUrl = $upload['baseurl'];

        add_filter('the_content', [$this, 'process'], 80);
    }

    public function process($content) {
        if (empty($content) || is_admin() || wp_doing_ajax()) {
            return $content;
        }

        if (strpos($content, '<img') === false) {
            return $content;
        }

        return preg_replace_callback(
            '/<img\b([^>]*)>/i',
            [$this, 'handleTag'],
            $content
        );
    }

    private function handleTag($matches) {
        $tag = $matches[0];
        $attrs = $matches[1];

        if ($this->hasDimensions($attrs)) {
            return $tag;
        }

        $src = $this->extractSrc($attrs);
        if (empty($src)) {
            return $tag;
        }

        if ($this->isExternal($src)) {
            return $tag;
        }

        $dims = $this->resolve($src);
        if ($dims === false) {
            return $tag;
        }

        $inject = sprintf(' width="%d" height="%d"', $dims[0], $dims[1]);
        return str_replace('<img', '<img' . $inject, $tag);
    }

    private function hasDimensions($attrs) {
        $hasWidth = preg_match('/\bwidth\s*=\s*["\']?\d+/i', $attrs);
        $hasHeight = preg_match('/\bheight\s*=\s*["\']?\d+/i', $attrs);
        return $hasWidth && $hasHeight;
    }

    private function extractSrc($attrs) {
        if (preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $attrs, $m)) {
            return $m[1];
        }
        return '';
    }

    private function isExternal($src) {
        $home = wp_parse_url(home_url(), PHP_URL_HOST);
        $parsed = wp_parse_url($src, PHP_URL_HOST);

        if (empty($parsed)) {
            return false;
        }

        return strtolower($parsed) !== strtolower($home);
    }

    private function resolve($src) {
        $hash = md5($src);
        $key = $this->transientPrefix . $hash;

        $cached = get_transient($key);
        if ($cached !== false) {
            return $cached === 'none' ? false : $cached;
        }

        $local = $this->toLocalPath($src);
        if ($local === false || !file_exists($local)) {
            set_transient($key, 'none', WEEK_IN_SECONDS);
            return false;
        }

        $info = @getimagesize($local);
        if ($info === false || empty($info[0]) || empty($info[1])) {
            set_transient($key, 'none', WEEK_IN_SECONDS);
            return false;
        }

        $dims = [(int) $info[0], (int) $info[1]];
        set_transient($key, $dims, MONTH_IN_SECONDS);
        return $dims;
    }

    private function toLocalPath($src) {
        $src = strtok($src, '?');

        if (strpos($src, $this->uploadUrl) !== false) {
            return str_replace($this->uploadUrl, $this->uploadDir, $src);
        }

        $siteUrl = site_url();
        if (strpos($src, $siteUrl) !== false) {
            $relative = str_replace($siteUrl, '', $src);
            $path = ABSPATH . ltrim($relative, '/');
            return file_exists($path) ? $path : false;
        }

        if (strpos($src, '/') === 0 && strpos($src, '//') !== 0) {
            $path = ABSPATH . ltrim($src, '/');
            return file_exists($path) ? $path : false;
        }

        return false;
    }
}
