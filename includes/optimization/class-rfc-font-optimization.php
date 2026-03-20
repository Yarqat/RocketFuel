<?php
defined('ABSPATH') || exit;

final class RFC_Font_Optimization {

    private $settings;
    private $google_font_handles = [];
    private $fonts_dir;
    private $fonts_url;

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        if ($this->settings->isSafeMode()) {
            return;
        }

        $this->fonts_dir = RFC_CACHE_DIR . 'fonts/';
        $this->fonts_url = content_url('/cache/rocketfuel/fonts/');

        if ($this->settings->get('disable_google_fonts', false)) {
            add_action('wp_enqueue_scripts', [$this, 'removeGoogleFonts'], 9999);
            return;
        }

        add_action('wp_enqueue_scripts', [$this, 'detectGoogleFonts'], 100);

        if ($this->settings->get('local_google_fonts', true)) {
            add_filter('style_loader_tag', [$this, 'interceptGoogleFont'], 5, 4);
        }

        add_action('wp_head', [$this, 'outputPreloads'], 2);
        add_filter('style_loader_tag', [$this, 'addFontDisplay'], 20, 4);
    }

    public function removeGoogleFonts() {
        global $wp_styles;
        if (!$wp_styles instanceof \WP_Styles) {
            return;
        }

        foreach ($wp_styles->registered as $handle => $dep) {
            if ($this->isGoogleFont($dep->src ?? '')) {
                wp_deregister_style($handle);
                wp_dequeue_style($handle);
            }
        }
    }

    public function detectGoogleFonts() {
        global $wp_styles;
        if (!$wp_styles instanceof \WP_Styles) {
            return;
        }

        foreach ($wp_styles->registered as $handle => $dep) {
            if ($this->isGoogleFont($dep->src ?? '')) {
                $this->google_font_handles[$handle] = $dep->src;
            }
        }
    }

    public function interceptGoogleFont($tag, $handle, $href, $media) {
        if (!isset($this->google_font_handles[$handle])) {
            return $tag;
        }

        $local_css = $this->localizeFont($href, $handle);
        if ($local_css === false) {
            return $this->applyFontDisplayToTag($tag);
        }

        $local_url = $this->pathToUrl($local_css);
        $tag = str_replace($href, $local_url, $tag);

        return $tag;
    }

    public function addFontDisplay($tag, $handle, $href, $media) {
        if (!isset($this->google_font_handles[$handle])) {
            return $tag;
        }

        $display = $this->settings->get('font_display', 'swap');
        if (strpos($href, 'display=') === false && strpos($tag, 'display=') === false) {
            $separator = strpos($href, '?') !== false ? '&' : '?';
            $new_href = $href . $separator . 'display=' . $display;
            $tag = str_replace($href, $new_href, $tag);
        }

        return $tag;
    }

    public function outputPreloads() {
        if (is_admin()) {
            return;
        }

        $raw = $this->settings->get('preload_fonts', '');
        if (empty($raw)) {
            return;
        }

        $fonts = array_filter(array_map('trim', preg_split('/[\n,]+/', $raw)));

        foreach ($fonts as $font_url) {
            $type = $this->detectFontType($font_url);
            echo '<link rel="preload" href="' . esc_url($font_url) . '" as="font" type="' . esc_attr($type) . '" crossorigin>' . "\n";
        }
    }

    private function localizeFont($url, $handle) {
        $hash = md5($url);
        $css_filename = 'rfc-gf-' . $handle . '-' . substr($hash, 0, 8) . '.css';
        $css_path = $this->fonts_dir . $css_filename;

        if (file_exists($css_path) && (time() - filemtime($css_path)) < 604800) {
            return $css_path;
        }

        $css_content = $this->fetchGoogleCSS($url);
        if (empty($css_content)) {
            return false;
        }

        if (!is_dir($this->fonts_dir)) {
            wp_mkdir_p($this->fonts_dir);
        }

        $css_content = $this->downloadAndRewriteFonts($css_content, $handle);
        $css_content = $this->injectFontDisplay($css_content);

        @file_put_contents($css_path, $css_content);

        return $css_path;
    }

    private function fetchGoogleCSS($url) {
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }

        $response = wp_remote_get($url, [
            'timeout'    => 10,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers'    => [
                'Accept' => 'text/css,*/*;q=0.1',
            ],
        ]);

        if (is_wp_error($response)) {
            return '';
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return '';
        }

        return wp_remote_retrieve_body($response);
    }

    private function downloadAndRewriteFonts($css, $handle) {
        return preg_replace_callback('/url\(\s*[\'"]?(https?:\/\/[^\'"\)]+\.(?:woff2?|ttf|eot|otf)(?:\?[^\'"\)]*)?)[\'"]?\s*\)/', function ($m) use ($handle) {
            $remote_url = $m[1];
            $ext = $this->getExtFromUrl($remote_url);
            $font_hash = substr(md5($remote_url), 0, 10);
            $local_name = $handle . '-' . $font_hash . '.' . $ext;
            $local_path = $this->fonts_dir . $local_name;

            if (!file_exists($local_path)) {
                $this->downloadFont($remote_url, $local_path);
            }

            if (file_exists($local_path)) {
                return 'url(' . $this->fonts_url . $local_name . ')';
            }

            return $m[0];
        }, $css);
    }

    private function downloadFont($url, $dest) {
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'stream'  => true,
            'filename' => $dest,
        ]);

        if (is_wp_error($response)) {
            @unlink($dest);
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            @unlink($dest);
            return false;
        }

        return true;
    }

    private function injectFontDisplay($css) {
        $display = $this->settings->get('font_display', 'swap');

        if (strpos($css, 'font-display') !== false) {
            $css = preg_replace('/font-display\s*:\s*[^;]+;/', 'font-display:' . $display . ';', $css);
        } else {
            $css = preg_replace('/@font-face\s*\{/', '@font-face{font-display:' . $display . ';', $css);
        }

        return $css;
    }

    private function applyFontDisplayToTag($tag) {
        $display = $this->settings->get('font_display', 'swap');

        if (preg_match('/href=["\']([^"\']+)["\']/', $tag, $href_match)) {
            $url = $href_match[1];
            if (strpos($url, 'display=') === false) {
                $sep = strpos($url, '?') !== false ? '&' : '?';
                $new_url = $url . $sep . 'display=' . $display;
                $tag = str_replace($url, $new_url, $tag);
            }
        }

        return $tag;
    }

    private function isGoogleFont($src) {
        if (empty($src)) {
            return false;
        }

        return strpos($src, 'fonts.googleapis.com') !== false
            || strpos($src, 'fonts.gstatic.com') !== false;
    }

    private function getExtFromUrl($url) {
        $path = wp_parse_url($url, PHP_URL_PATH);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return in_array($ext, ['woff', 'woff2', 'ttf', 'eot', 'otf'], true) ? $ext : 'woff2';
    }

    private function detectFontType($url) {
        $ext = pathinfo(wp_parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        $map = [
            'woff2' => 'font/woff2',
            'woff'  => 'font/woff',
            'ttf'   => 'font/ttf',
            'otf'   => 'font/otf',
            'eot'   => 'application/vnd.ms-fontobject',
        ];

        return $map[$ext] ?? 'font/woff2';
    }

    private function pathToUrl($path) {
        $content_dir = WP_CONTENT_DIR;
        if (strpos($path, $content_dir) === 0) {
            return content_url(substr($path, strlen($content_dir)));
        }

        return $this->fonts_url . basename($path);
    }
}
