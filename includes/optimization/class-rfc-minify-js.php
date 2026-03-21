<?php
defined('ABSPATH') || exit;

final class RFC_Minify_JS {

    private $settings;
    private $exclusions = [];
    private $header_handles = [];
    private $footer_handles = [];
    private $processed = [];
    private $never_combine = ['jquery', 'jquery-core', 'jquery-migrate'];

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        if ($this->settings->isSafeMode()) {
            return;
        }

        $this->exclusions = $this->parseExclusions();

        add_action('wp_enqueue_scripts', [$this, 'collectScripts'], 9999);
        add_filter('script_loader_tag', [$this, 'rewriteTag'], 10, 3);
    }

    public function collectScripts() {
        if (is_admin()) {
            return;
        }

        if (!$this->settings->get('combine_js', false)) {
            return;
        }

        global $wp_scripts;
        if (!$wp_scripts instanceof \WP_Scripts) {
            return;
        }

        $wp_scripts->all_deps($wp_scripts->queue);

        foreach ($wp_scripts->to_do as $handle) {
            if ($this->isExcluded($handle) || in_array($handle, $this->never_combine, true)) {
                continue;
            }

            $obj = $wp_scripts->registered[$handle] ?? null;
            if (!$obj || empty($obj->src)) {
                continue;
            }

            if (!$this->isLocal($obj->src)) {
                continue;
            }

            $in_footer = isset($obj->extra['group']) && $obj->extra['group'] === 1;
            if ($in_footer) {
                $this->footer_handles[] = $handle;
            } else {
                $this->header_handles[] = $handle;
            }
        }

        $this->buildCombined($this->header_handles, 'header');
        $this->buildCombined($this->footer_handles, 'footer');
    }

    public function rewriteTag($tag, $handle, $src) {
        if (is_admin()) {
            return $tag;
        }

        if (in_array($handle, $this->processed, true)) {
            return '';
        }

        if ($this->isExcluded($handle)) {
            return $tag;
        }

        if (!$this->settings->get('combine_js', false) && $this->isLocal($src)) {
            $min_path = $this->minifySingle($handle, $src);
            if ($min_path !== false) {
                $min_url = $this->pathToUrl($min_path);
                $tag = str_replace($src, $min_url, $tag);
            }
        }

        return $tag;
    }

    private function buildCombined($handles, $position) {
        if (empty($handles)) {
            return;
        }

        global $wp_scripts;
        $combined = '';
        $sources = [];
        $deps_for_combined = [];

        foreach ($handles as $handle) {
            $obj = $wp_scripts->registered[$handle] ?? null;
            if (!$obj) {
                continue;
            }

            $file = $this->srcToPath($obj->src);
            if (!$file || !file_exists($file)) {
                continue;
            }

            $js = @file_get_contents($file);
            if ($js === false) {
                continue;
            }

            $js = $this->minifyContent($js);

            $before = '';
            if (!empty($obj->extra['before'])) {
                $before = implode("\n", $obj->extra['before']) . "\n";
            }

            $after = '';
            if (!empty($obj->extra['after'])) {
                $after = "\n" . implode("\n", $obj->extra['after']);
            }

            $localize = '';
            if (!empty($obj->extra['data'])) {
                $localize = $obj->extra['data'] . "\n";
            }

            $combined .= $localize . $before . $js . $after . ";\n";
            $sources[] = $handle;

            foreach ($obj->deps as $dep) {
                if (!in_array($dep, $handles, true) && !in_array($dep, $deps_for_combined, true)) {
                    $deps_for_combined[] = $dep;
                }
            }
        }

        if (empty($combined)) {
            return;
        }

        $hash = substr(md5($combined), 0, 12);
        $filename = "rfc-{$position}-{$hash}.js";
        $dir = RFC_MIN_DIR . 'js/';

        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        $filepath = $dir . $filename;
        if (!file_exists($filepath)) {
            @file_put_contents($filepath, $combined);
        }

        $url = content_url('/cache/rocketfuel/min/js/' . $filename);

        foreach ($sources as $h) {
            wp_dequeue_script($h);
            $this->processed[] = $h;
        }

        $combo_handle = "rfc-combined-js-{$position}";
        $in_footer = ($position === 'footer');
        wp_enqueue_script($combo_handle, $url, $deps_for_combined, null, $in_footer);
    }

    private function minifySingle($handle, $src) {
        if (in_array($handle, $this->never_combine, true)) {
            return false;
        }

        $file = $this->srcToPath($src);
        if (!$file || !file_exists($file)) {
            return false;
        }

        if (strpos($file, RFC_MIN_DIR) === 0) {
            return false;
        }

        $js = @file_get_contents($file);
        if ($js === false || strlen($js) < 10) {
            return false;
        }

        if ($this->looksMinified($js)) {
            return false;
        }

        $minified = $this->minifyContent($js);
        $hash = substr(md5($minified), 0, 12);
        $filename = "rfc-{$handle}-{$hash}.js";
        $dir = RFC_MIN_DIR . 'js/';

        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        $output = $dir . $filename;
        if (!file_exists($output)) {
            @file_put_contents($output, $minified);
        }

        return $output;
    }

    private function minifyContent($js) {
        $strings = [];
        $sidx = 0;

        $js = preg_replace_callback('/`(?:[^`\\\\]|\\\\.)*`/', function ($m) use (&$strings, &$sidx) {
            $token = "RFC__TPL_{$sidx}__";
            $strings[$token] = $m[0];
            $sidx++;
            return $token;
        }, $js);

        $js = preg_replace_callback('/(["\'])(?:(?!\1|\\\\).|\\\\.)*\1/', function ($m) use (&$strings, &$sidx) {
            $token = "RFC__STR_{$sidx}__";
            $strings[$token] = $m[0];
            $sidx++;
            return $token;
        }, $js);

        $js = preg_replace('#//[^\n]*#', '', $js);
        $js = preg_replace('#/\*.*?\*/#s', '', $js);
        $js = preg_replace('/[ \t]+/', ' ', $js);
        $js = preg_replace('/\s*\n\s*/', "\n", $js);
        $js = preg_replace('/\n{2,}/', "\n", $js);
        $js = preg_replace('/\n\s*([{}()\[\];,:.+\-*\/=<>!&|?])/', '$1', $js);
        $js = preg_replace('/([{}()\[\];,:.+\-*\/=<>!&|?])\s*\n/', '$1', $js);

        if (!empty($strings)) {
            $js = str_replace(array_keys($strings), array_values($strings), $js);
        }

        return trim($js);
    }

    private function isExcluded($handle) {
        if (in_array($handle, $this->exclusions, true)) {
            return true;
        }

        global $wp_scripts;
        $obj = $wp_scripts->registered[$handle] ?? null;
        if ($obj && !empty($obj->src)) {
            foreach ($this->exclusions as $exc) {
                if (strpos($obj->src, $exc) !== false) {
                    return true;
                }
            }
        }

        return apply_filters('rfc_exclude_js', false, $handle);
    }

    private function parseExclusions() {
        $raw = $this->settings->get('js_exclusions', '');
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

    private function looksMinified($js) {
        $lines = substr_count($js, "\n");
        $length = strlen($js);

        if ($lines === 0 && $length > 500) {
            return true;
        }

        if ($length > 0 && ($lines / $length) < 0.005) {
            return true;
        }

        return false;
    }
}
