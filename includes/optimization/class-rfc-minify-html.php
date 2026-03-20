<?php
defined('ABSPATH') || exit;

final class RFC_Minify_HTML {

    private $settings;
    private $active = false;

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        if ($this->settings->isSafeMode()) {
            return;
        }

        add_action('template_redirect', [$this, 'startBuffer'], -998);
    }

    public function startBuffer() {
        if (is_admin() || is_customize_preview() || is_feed()) {
            return;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        $this->active = true;
        ob_start([$this, 'processOutput']);
    }

    public function processOutput($html) {
        if (!$this->active || strlen($html) < 100) {
            return $html;
        }

        if (strpos($html, '</html>') === false) {
            return $html;
        }

        $html = apply_filters('rfc_before_minify_html', $html);

        if ($this->settings->get('remove_html_comments', true)) {
            $html = $this->stripComments($html);
        }

        $html = $this->collapseWhitespace($html);
        $html = $this->minifyInlineCSS($html);
        $html = $this->minifyInlineJS($html);

        return apply_filters('rfc_after_minify_html', $html);
    }

    private function stripComments($html) {
        $preserved = [];
        $idx = 0;

        $html = preg_replace_callback('/<!--\[if\s[^\]]*\]>.*?<!\[endif\]-->/is', function ($m) use (&$preserved, &$idx) {
            $key = '<!--RFC_PRESERVE_' . $idx . '-->';
            $preserved[$key] = $m[0];
            $idx++;
            return $key;
        }, $html);

        $html = preg_replace_callback('/<!--\s*RocketFuel.*?-->/is', function ($m) use (&$preserved, &$idx) {
            $key = '<!--RFC_PRESERVE_' . $idx . '-->';
            $preserved[$key] = $m[0];
            $idx++;
            return $key;
        }, $html);

        $html = preg_replace_callback('/<!--\s*Cached by RocketFuel.*?-->/is', function ($m) use (&$preserved, &$idx) {
            $key = '<!--RFC_PRESERVE_' . $idx . '-->';
            $preserved[$key] = $m[0];
            $idx++;
            return $key;
        }, $html);

        $html = preg_replace('/<!--(?!\s*noindex)(?!\s*\/noindex).*?-->/s', '', $html);

        if (!empty($preserved)) {
            $html = str_replace(array_keys($preserved), array_values($preserved), $html);
        }

        return $html;
    }

    private function collapseWhitespace($html) {
        $preserved = [];
        $idx = 0;

        $html = preg_replace_callback('/<(pre|textarea|script|style)\b[^>]*>.*?<\/\1>/is', function ($m) use (&$preserved, &$idx) {
            $placeholder = 'RFC_WS_HOLD_' . $idx;
            $preserved[$placeholder] = $m[0];
            $idx++;
            return $placeholder;
        }, $html);

        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '> <', $html);
        $html = preg_replace('/>\s+<\//', '></', $html);
        $html = preg_replace('/\s*\n\s*/', "\n", $html);
        $html = preg_replace('/\n+/', "\n", $html);

        if (!empty($preserved)) {
            $html = str_replace(array_keys($preserved), array_values($preserved), $html);
        }

        return $html;
    }

    private function minifyInlineCSS($html) {
        return preg_replace_callback('/<style\b([^>]*)>(.*?)<\/style>/is', function ($m) {
            $attrs = $m[1];
            $css = $m[2];

            if (strpos($attrs, 'data-rfc-skip') !== false) {
                return $m[0];
            }

            $css = preg_replace('!/\*.*?\*/!s', '', $css);
            $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
            $css = preg_replace('/;\s*}/', '}', $css);
            $css = preg_replace('/\s+/', ' ', $css);
            $css = trim($css);

            return '<style' . $attrs . '>' . $css . '</style>';
        }, $html);
    }

    private function minifyInlineJS($html) {
        return preg_replace_callback('/<script\b([^>]*)>(.*?)<\/script>/is', function ($m) {
            $attrs = $m[1];
            $js = $m[2];

            if (empty(trim($js))) {
                return $m[0];
            }

            if (strpos($attrs, 'data-rfc-skip') !== false) {
                return $m[0];
            }

            if (strpos($attrs, 'type=') !== false && strpos($attrs, 'text/javascript') === false && strpos($attrs, 'module') === false) {
                return $m[0];
            }

            $preserved = [];
            $pidx = 0;

            $js = preg_replace_callback('/(["\'])(?:(?!\1|\\\\).|\\\\.)*\1/', function ($sm) use (&$preserved, &$pidx) {
                $token = 'RFC_JSSTR_' . $pidx;
                $preserved[$token] = $sm[0];
                $pidx++;
                return $token;
            }, $js);

            $js = preg_replace('!//[^\n]*!', '', $js);
            $js = preg_replace('!/\*.*?\*/!s', '', $js);
            $js = preg_replace('/[ \t]+/', ' ', $js);
            $js = preg_replace('/\s*\n\s*/', "\n", $js);
            $js = preg_replace('/\n+/', "\n", $js);
            $js = trim($js);

            if (!empty($preserved)) {
                $js = str_replace(array_keys($preserved), array_values($preserved), $js);
            }

            return '<script' . $attrs . '>' . $js . '</script>';
        }, $html);
    }
}
