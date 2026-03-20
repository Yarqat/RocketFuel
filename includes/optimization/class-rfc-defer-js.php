<?php
defined('ABSPATH') || exit;

final class RFC_Defer_JS {

    private $settings;
    private $exclusions = [];
    private $protected_handles = ['jquery', 'jquery-core'];

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        if ($this->settings->isSafeMode()) {
            return;
        }

        $this->exclusions = $this->parseExclusions();

        add_filter('script_loader_tag', [$this, 'addDefer'], 15, 3);

        if ($this->settings->get('remove_jquery_migrate', false)) {
            add_action('wp_default_scripts', [$this, 'stripMigrate']);
        }
    }

    public function addDefer($tag, $handle, $src) {
        if (is_admin()) {
            return $tag;
        }

        if (in_array($handle, $this->protected_handles, true)) {
            return $tag;
        }

        if ($this->isExcluded($handle, $src)) {
            return $tag;
        }

        if (strpos($tag, 'defer') !== false || strpos($tag, 'async') !== false) {
            return $tag;
        }

        if (strpos($tag, 'type=') !== false && strpos($tag, 'text/javascript') === false) {
            return $tag;
        }

        $tag = str_replace(' src=', ' defer src=', $tag);

        return apply_filters('rfc_defer_script_tag', $tag, $handle, $src);
    }

    public function stripMigrate($scripts) {
        if (is_admin()) {
            return;
        }

        if (!isset($scripts->registered['jquery'])) {
            return;
        }

        $jquery = $scripts->registered['jquery'];
        if (!$jquery->deps) {
            return;
        }

        $jquery->deps = array_diff($jquery->deps, ['jquery-migrate']);
    }

    private function isExcluded($handle, $src) {
        if (in_array($handle, $this->exclusions, true)) {
            return true;
        }

        foreach ($this->exclusions as $pattern) {
            if (strpos($handle, $pattern) !== false) {
                return true;
            }

            if (!empty($src) && strpos($src, $pattern) !== false) {
                return true;
            }
        }

        global $wp_scripts;
        $obj = $wp_scripts->registered[$handle] ?? null;
        if ($obj && !empty($obj->extra['data'])) {
            return true;
        }

        return apply_filters('rfc_exclude_defer', false, $handle, $src);
    }

    private function parseExclusions() {
        $raw = $this->settings->get('defer_js_exclusions', '');
        if (empty($raw)) {
            return [];
        }

        $list = array_filter(array_map('trim', preg_split('/[\n,]+/', $raw)));

        return array_merge($this->protected_handles, $list);
    }
}
