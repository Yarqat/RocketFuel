<?php
defined('ABSPATH') || exit;

final class RFC_Settings {

    private static $instance = null;
    private $data = [];
    private $dirty = false;
    private $option_key = 'rfc_settings';

    private $schema = [
        'page_cache_enabled'       => true,
        'cache_lifespan'           => 36000,
        'mobile_cache'             => false,
        'logged_in_cache'          => false,
        'cache_query_strings'      => '',
        'strip_query_strings'      => 'utm_source,utm_medium,utm_campaign,utm_term,utm_content,fbclid,gclid,mc_cid,mc_eid',
        'never_cache_urls'         => '',
        'never_cache_cookies'      => 'wordpress_logged_in_*,woocommerce_items_in_cart,wp_woocommerce_session_*',
        'never_cache_user_agents'  => '',
        'purge_on_post_update'     => true,
        'purge_homepage'           => true,
        'purge_archives'           => true,
        'purge_pagination'         => false,

        'minify_html'              => true,
        'remove_html_comments'     => true,
        'minify_css'               => true,
        'combine_css'              => false,
        'css_exclusions'           => '',
        'minify_js'                => true,
        'combine_js'               => false,
        'js_exclusions'            => '',
        'defer_js'                 => true,
        'defer_js_exclusions'      => 'jquery-core',
        'remove_jquery_migrate'    => false,

        'lazy_load_images'         => true,
        'lazy_load_method'         => 'native_js',
        'lazy_load_exclude_count'  => 2,
        'lazy_load_placeholder'    => 'transparent',
        'lazy_load_iframes'        => true,
        'lazy_load_videos'         => true,
        'youtube_thumbnail_swap'   => true,
        'vimeo_thumbnail_swap'     => true,
        'lazy_load_exclusions'     => '',
        'add_missing_dimensions'   => true,

        'local_google_fonts'       => true,
        'font_display'             => 'swap',
        'disable_google_fonts'     => false,
        'preload_fonts'            => '',
        'dns_prefetch_urls'        => '',
        'preconnect_urls'          => '',
        'preload_enabled'          => true,
        'preload_sitemap_url'      => '',
        'preload_rate'             => 'normal',

        'disable_emojis'           => true,
        'disable_embeds'           => false,
        'disable_dashicons'        => true,
        'disable_xml_rpc'          => false,
        'disable_rss'              => false,
        'disable_self_pingbacks'   => true,
        'disable_rest_api_public'  => false,
        'rest_api_allowlist'       => '',
        'remove_wp_version'        => true,
        'remove_wlmanifest'        => true,
        'remove_rsd'               => true,
        'remove_shortlink'         => true,
        'remove_query_strings'     => true,
        'disable_google_maps'      => false,
        'disable_comments'         => false,
        'disable_gravatar'         => false,
        'disable_block_library_css' => false,
        'disable_global_styles'    => false,
        'disable_wc_bloat'         => false,
        'limit_revisions'          => false,
        'revisions_count'          => 5,

        'heartbeat_dashboard'      => 'reduce',
        'heartbeat_editor'         => 'reduce',
        'heartbeat_frontend'       => 'disable',
        'heartbeat_frequency'      => 60,

        'cdn_enabled'              => false,
        'cdn_url'                  => '',
        'cdn_directories'          => 'wp-content,wp-includes',
        'cdn_excluded_extensions'  => '.php',
        'cdn_excluded_urls'        => '',

        'db_revisions'             => true,
        'db_auto_drafts'           => true,
        'db_trashed_posts'         => true,
        'db_spam_comments'         => true,
        'db_trashed_comments'      => true,
        'db_expired_transients'    => true,
        'db_optimize_tables'       => true,
        'db_orphaned_meta'         => true,

        'safe_mode'                => false,
        'debug_log'                => false,
        'hide_wp_version'          => true,
        'disable_file_editor'      => false,
        'disable_directory_browsing' => true,
        'block_author_scans'       => true,

        'security_headers_enabled' => false,
        'header_x_content_type'    => true,
        'header_x_frame_options'   => 'SAMEORIGIN',
        'header_x_xss_protection'  => true,
        'header_referrer_policy'   => 'strict-origin-when-cross-origin',
        'header_permissions_policy' => 'camera=(), microphone=(), geolocation=()',
        'header_csp'               => '',
        'change_login_url'         => false,
        'login_url_slug'           => '',
        'login_redirect_mode'      => '404',

        'white_label_enabled'      => false,
        'white_label_name'         => '',
        'white_label_logo_url'     => '',
        'white_label_support_url'  => '',
    ];

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $stored = get_option($this->option_key, []);
        $this->data = is_array($stored) ? array_merge($this->schema, $stored) : $this->schema;
    }

    public function get($key, $fallback = null) {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        return $fallback !== null ? $fallback : ($this->schema[$key] ?? null);
    }

    public function set($key, $value) {
        $this->data[$key] = $this->sanitize($key, $value);
        $this->dirty = true;
        return $this;
    }

    public function bulk($pairs) {
        foreach ($pairs as $k => $v) {
            $this->set($k, $v);
        }
        return $this;
    }

    public function save() {
        if (!$this->dirty) {
            return false;
        }
        $result = update_option($this->option_key, $this->data, true);
        if ($result) {
            $this->dirty = false;
            do_action('rfc_settings_updated', $this->data);
        }
        return $result;
    }

    public function all() {
        return $this->data;
    }

    public function defaults() {
        return $this->schema;
    }

    public function reset() {
        $this->data = $this->schema;
        $this->dirty = true;
        return $this->save();
    }

    public function export() {
        return wp_json_encode($this->data, JSON_PRETTY_PRINT);
    }

    public function import($json) {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return false;
        }
        foreach ($decoded as $k => $v) {
            if (array_key_exists($k, $this->schema)) {
                $this->set($k, $v);
            }
        }
        return $this->save();
    }

    public function isSafeMode() {
        return (bool) $this->get('safe_mode', false);
    }

    private function sanitize($key, $value) {
        if (!isset($this->schema[$key])) {
            return $value;
        }

        $default = $this->schema[$key];

        if (is_bool($default)) {
            return (bool) $value;
        }

        if (is_int($default)) {
            return (int) $value;
        }

        if (is_string($default)) {
            return sanitize_textarea_field($value);
        }

        return $value;
    }
}
