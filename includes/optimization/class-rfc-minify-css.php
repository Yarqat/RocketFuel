<?php
defined('ABSPATH') || exit;

final class RFC_Minify_CSS {

    private $settings;
    private $exclusions = [];
    private $header_handles = [];
    private $footer_handles = [];
    private $processed = [];

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        if ($this->settings->isSafeMode()) {
            return;
        }

        $this->exclusions = $this->parseExclusions();

        add_action('wp_enqueue_scripts', [$this, 'collectStyles'], 9999);
        add_filter('style_loader_tag', [$this, 'rewriteTag'], 10, 4);
    }

    public function collectStyles() {
        if (is_admin()) {
            return;
        }

        if (!$this->settings->get('combine_css', false)) {
            return;
        }

        global $wp_styles;
        if (!$wp_styles instanceof \WP_Styles) {
            return;
        }

        $wp_styles->all_deps($wp_styles->queue);

        foreach ($wp_styles->to_do as $handle) {
            if ($this->isExcluded($handle)) {
                continue;
            }

            $obj = $wp_styles->registered[$handle] ?? null;
            if (!$obj || empty($obj->src)) {
                continue;
            }

            if (!$this->isLocal($obj->src)) {
                continue;
            }

            $group = $obj->extra['group'] ?? 0;
            if ($group === 1) {
                $this->footer_handles[] = $handle;
            } else {
                $this->header_handles[] = $handle;
            }
        }

        $this->buildCombined($this->header_handles, 'header');
        $this->buildCombined($this->footer_handles, 'footer');
    }

    public function rewriteTag($tag, $handle, $href, $media) {
        if (is_admin()) {
            return $tag;
        }

        if (in_array($handle, $this->processed, true)) {
            return '';
        }

        if ($this->isExcluded($handle)) {
            return $tag;
        }

        if (!$this->settings->get('combine_css', false) && $this->isLocal($href)) {
            $min_path = $this->minifySingle($handle, $href);
            if ($min_path !== false) {
                $min_url = $this->pathToUrl($min_path);
                $tag = str_replace($href, $min_url, $tag);
            }
        }

        return $tag;
    }

    private function buildCombined($handles, $position) {
        if (empty($handles)) {
            return;
        }

        global $wp_styles;
        $combined = '';
        $sources = [];

        foreach ($handles as $handle) {
            $obj = $wp_styles->registered[$handle] ?? null;
            if (!$obj) {
                continue;
            }

            $file = $this->srcToPath($obj->src);
            if (!$file || !file_exists($file)) {
                continue;
            }

            $css = @file_get_contents($file);
            if ($css === false) {
                continue;
            }

            $css = $this->resolveRelativeUrls($css, dirname($file));
            $css = $this->minifyContent($css);

            if (!empty($obj->extra['after'])) {
                $css .= "\n" . implode("\n", $obj->extra['after']);
            }

            $combined .= $css . "\n";
            $sources[] = $handle;
        }

        if (empty($combined)) {
            return;
        }

        $hash = substr(md5($combined), 0, 12);
        $filename = "rfc-{$position}-{$hash}.css";
        $dir = RFC_MIN_DIR . 'css/';

        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        $filepath = $dir . $filename;
        if (!file_exists($filepath)) {
            @file_put_contents($filepath, $combined);
        }

        $url = content_url('/cache/rocketfuel/min/css/' . $filename);

        foreach ($sources as $h) {
            wp_dequeue_style($h);
            $this->processed[] = $h;
        }

        $combo_handle = "rfc-combined-{$position}";
        wp_enqueue_style($combo_handle, $url, [], null, 'all');

        if ($position === 'footer') {
            $GLOBALS['wp_styles']->registered[$combo_handle]->extra['group'] = 1;
        }
    }

    private function minifySingle($handle, $src) {
        $file = $this->srcToPath($src);
        if (!$file || !file_exists($file)) {
            return false;
        }

        if (strpos($file, RFC_MIN_DIR) === 0) {
            return false;
        }

        $css = @file_get_contents($file);
        if ($css === false || strlen($css) < 10) {
            return false;
        }

        if ($this->looksMinified($css)) {
            return false;
        }

        $css = $this->resolveRelativeUrls($css, dirname($file));
        $minified = $this->minifyContent($css);
        $hash = substr(md5($minified), 0, 12);
        $filename = "rfc-{$handle}-{$hash}.css";
        $dir = RFC_MIN_DIR . 'css/';

        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        $output = $dir . $filename;
        if (!file_exists($output)) {
            @file_put_contents($output, $minified);
        }

        return $output;
    }

    private function minifyContent($css) {
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        $css = preg_replace('/\s*([{}:;,>+~!])\s*/', '$1', $css);
        $css = preg_replace('/;\s*}/', '}', $css);
        $css = str_replace(['  ', "\r", "\t"], ['', '', ''], $css);
        $css = preg_replace('/\s+/', ' ', $css);

        $css = preg_replace('/(?:margin|padding):([^\s;]+)\s+\1\s+\1\s+\1/', 'margin:$1', $css);
        $css = preg_replace('/#([0-9a-fA-F])\1([0-9a-fA-F])\2([0-9a-fA-F])\3/', '#$1$2$3', $css);
        $css = preg_replace('/\b0(?:px|em|rem|%|vh|vw|ex|ch|cm|mm|in|pt|pc)/', '0', $css);
        $css = str_replace(':0 0 0 0', ':0', $css);
        $css = str_replace(':0 0 0;', ':0;', $css);
        $css = str_replace(':0 0;', ':0;', $css);
        $css = str_replace('background-color:', 'background-color:', $css);

        return trim($css);
    }

    private function resolveRelativeUrls($css, $dir) {
        return preg_replace_callback('/url\(\s*[\'"]?\s*(?!data:|https?:|\/\/)([^\'"\)]+)\s*[\'"]?\s*\)/', function ($m) use ($dir) {
            $resolved = realpath($dir . '/' . $m[1]);
            if (!$resolved) {
                return $m[0];
            }

            $content_dir = realpath(WP_CONTENT_DIR);
            if (strpos($resolved, $content_dir) !== 0) {
                return $m[0];
            }

            $relative = str_replace($content_dir, '', $resolved);
            $url = content_url($relative);
            return 'url(' . $url . ')';
        }, $css);
    }

    private function isExcluded($handle) {
        if (in_array($handle, $this->exclusions, true)) {
            return true;
        }

        global $wp_styles;
        $obj = $wp_styles->registered[$handle] ?? null;
        if ($obj && !empty($obj->src)) {
            foreach ($this->exclusions as $exc) {
                if (strpos($obj->src, $exc) !== false) {
                    return true;
                }
            }
        }

        return apply_filters('rfc_exclude_css', false, $handle);
    }

    private function parseExclusions() {
        $raw = $this->settings->get('css_exclusions', '');
        if (empty($raw)) {
            return [];
        }

        return array_filter(array_map('trim', preg_split('/[\n,]+/', $raw)));
    }

    private function isLocal($src) {
        if (strpos($src, '//') === false) {
            return true;
        }

        $home = wp_parse_url(home_url(), PHP_URL_HOST);
        $parsed = wp_parse_url($src, PHP_URL_HOST);

        return $parsed === $home || $parsed === null;
    }

    private function srcToPath($src) {
        if (strpos($src, '//') === false) {
            return ABSPATH . ltrim($src, '/');
        }

        $site_url = site_url('/');
        if (strpos($src, $site_url) === 0) {
            return ABSPATH . substr($src, strlen($site_url));
        }

        $content_url = content_url('/');
        if (strpos($src, $content_url) === 0) {
            return WP_CONTENT_DIR . '/' . substr($src, strlen($content_url));
        }

        $parsed = wp_parse_url($src);
        $path = $parsed['path'] ?? '';
        if (!empty($path)) {
            return ABSPATH . ltrim($path, '/');
        }

        return false;
    }

    private function pathToUrl($path) {
        $content_dir = WP_CONTENT_DIR;
        if (strpos($path, $content_dir) === 0) {
            return content_url(substr($path, strlen($content_dir)));
        }

        return site_url(str_replace(ABSPATH, '/', $path));
    }

    private function looksMinified($css) {
        $lines = substr_count($css, "\n");
        $length = strlen($css);

        if ($lines === 0 && $length > 500) {
            return true;
        }

        if ($length > 0 && ($lines / $length) < 0.005) {
            return true;
        }

        return false;
    }
}
