<?php
defined('ABSPATH') || exit;

final class RFC_Cleanup {

    private $settings;
    private $hooks = [];

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        if ($this->settings->isSafeMode()) {
            return;
        }

        add_action('init', [$this, 'register'], 1);
        add_action('wp_loaded', [$this, 'lateRegister'], 1);
    }

    public function register() {
        if ($this->settings->get('disable_emojis', true)) {
            $this->stripEmojis();
        }

        if ($this->settings->get('disable_embeds', false)) {
            $this->stripEmbeds();
        }

        if ($this->settings->get('disable_xml_rpc', false)) {
            $this->blockXmlRpc();
        }

        if ($this->settings->get('disable_rss', false)) {
            $this->stripRss();
        }

        if ($this->settings->get('disable_self_pingbacks', true)) {
            add_action('pre_ping', [$this, 'killSelfPings']);
        }

        if ($this->settings->get('disable_rest_api_public', false)) {
            add_filter('rest_authentication_errors', [$this, 'restrictRestApi'], 99);
        }

        if ($this->settings->get('remove_wp_version', true)) {
            $this->stripWpVersion();
        }

        if ($this->settings->get('remove_wlmanifest', true)) {
            remove_action('wp_head', 'wlwmanifest_link');
        }

        if ($this->settings->get('remove_rsd', true)) {
            remove_action('wp_head', 'rsd_link');
        }

        if ($this->settings->get('remove_shortlink', true)) {
            remove_action('wp_head', 'wp_shortlink_wp_head');
            remove_action('template_redirect', 'wp_shortlink_header', 11);
        }

        if ($this->settings->get('remove_query_strings', true)) {
            add_filter('script_loader_src', [$this, 'stripVersionQuery'], 9999);
            add_filter('style_loader_src', [$this, 'stripVersionQuery'], 9999);
        }

        if ($this->settings->get('disable_comments', false)) {
            $this->nukeComments();
        }

        if ($this->settings->get('disable_gravatar', false)) {
            add_filter('get_avatar_url', [$this, 'replaceGravatar'], 9999, 3);
        }

        if ($this->settings->get('limit_revisions', false)) {
            $count = (int) $this->settings->get('revisions_count', 5);
            add_filter('wp_revisions_to_keep', function () use ($count) {
                return max(0, $count);
            }, 9999);
        }
    }

    public function lateRegister() {
        if ($this->settings->get('disable_dashicons', true)) {
            add_action('wp_enqueue_scripts', [$this, 'dequeueDashicons'], 9999);
        }

        if ($this->settings->get('disable_block_library_css', false)) {
            add_action('wp_enqueue_scripts', [$this, 'dequeueBlockCss'], 9999);
        }

        if ($this->settings->get('disable_global_styles', false)) {
            add_action('wp_enqueue_scripts', [$this, 'dequeueGlobalStyles'], 9999);
        }

        if ($this->settings->get('disable_wc_bloat', false)) {
            $this->stripWcBloat();
        }
    }

    private function stripEmojis() {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

        add_filter('tiny_mce_plugins', function ($plugins) {
            return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : $plugins;
        });

        add_filter('wp_resource_hints', function ($urls, $relation) {
            if ($relation === 'dns-prefetch') {
                $urls = array_filter($urls, function ($url) {
                    return strpos($url, 'https://s.w.org/images/core/emoji/') === false;
                });
            }
            return $urls;
        }, 10, 2);
    }

    private function stripEmbeds() {
        remove_action('rest_api_init', 'wp_oembed_register_route');
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');

        add_filter('embed_oembed_discover', '__return_false');
        add_filter('tiny_mce_plugins', function ($plugins) {
            return array_diff($plugins, ['wpembed']);
        });

        add_action('wp_footer', function () {
            wp_dequeue_script('wp-embed');
        });
    }

    private function blockXmlRpc() {
        add_filter('xmlrpc_enabled', '__return_false');
        add_filter('wp_headers', function ($headers) {
            unset($headers['X-Pingback']);
            return $headers;
        });
        add_filter('xmlrpc_methods', function () {
            return [];
        });

        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'xmlrpc.php') !== false) {
            if (!headers_sent()) {
                http_response_code(403);
                exit('Forbidden');
            }
        }
    }

    private function stripRss() {
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);

        add_action('do_feed', [$this, 'redirectFeed'], 1);
        add_action('do_feed_rdf', [$this, 'redirectFeed'], 1);
        add_action('do_feed_rss', [$this, 'redirectFeed'], 1);
        add_action('do_feed_rss2', [$this, 'redirectFeed'], 1);
        add_action('do_feed_atom', [$this, 'redirectFeed'], 1);
    }

    public function redirectFeed() {
        wp_redirect(home_url(), 301);
        exit;
    }

    public function killSelfPings(&$links) {
        $home = home_url();
        foreach ($links as $i => $link) {
            if (strpos($link, $home) === 0) {
                unset($links[$i]);
            }
        }
    }

    public function restrictRestApi($result) {
        if ($result !== null) {
            return $result;
        }

        if (is_user_logged_in()) {
            return $result;
        }

        $allowlist = $this->settings->get('rest_api_allowlist', '');
        if (!empty($allowlist)) {
            $routes = array_filter(array_map('trim', explode("\n", $allowlist)));
            $current = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            foreach ($routes as $route) {
                if (!empty($route) && strpos($current, $route) !== false) {
                    return $result;
                }
            }
        }

        return new WP_Error(
            'rest_disabled',
            esc_html__('REST API access restricted.', 'rocketfuel-cache'),
            ['status' => 401]
        );
    }

    private function stripWpVersion() {
        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_empty_string');

        add_filter('script_loader_src', [$this, 'stripVersionQuery'], 9999);
        add_filter('style_loader_src', [$this, 'stripVersionQuery'], 9999);
    }

    public function stripVersionQuery($src) {
        if (empty($src)) {
            return $src;
        }

        $parsed = wp_parse_url($src);
        if (!isset($parsed['query'])) {
            return $src;
        }

        parse_str($parsed['query'], $params);
        if (isset($params['ver'])) {
            unset($params['ver']);
        }

        $clean = strtok($src, '?');
        if (!empty($params)) {
            $clean .= '?' . http_build_query($params);
        }

        return $clean;
    }

    public function dequeueDashicons() {
        if (is_user_logged_in()) {
            return;
        }
        wp_dequeue_style('dashicons');
        wp_deregister_style('dashicons');
    }

    public function dequeueBlockCss() {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wc-blocks-style');
    }

    public function dequeueGlobalStyles() {
        wp_dequeue_style('global-styles');
        wp_dequeue_style('classic-theme-styles');

        remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
        remove_action('wp_footer', 'wp_global_styles_render_svg_filters');
    }

    private function nukeComments() {
        add_filter('comments_open', '__return_false', 20);
        add_filter('pings_open', '__return_false', 20);
        add_filter('comments_array', '__return_empty_array', 10);

        add_action('admin_init', function () {
            $types = get_post_types(['public' => true], 'names');
            foreach ($types as $type) {
                remove_post_type_support($type, 'comments');
                remove_post_type_support($type, 'trackbacks');
            }
        });

        add_action('admin_menu', function () {
            remove_menu_page('edit-comments.php');
        }, 999);

        add_action('admin_bar_menu', function ($bar) {
            $bar->remove_node('comments');
        }, 999);

        add_action('wp_before_admin_bar_render', function () {
            global $wp_admin_bar;
            if ($wp_admin_bar instanceof WP_Admin_Bar) {
                $wp_admin_bar->remove_node('comments');
            }
        });
    }

    public function replaceGravatar($url, $id_or_email, $args) {
        if (strpos($url, 'gravatar.com') === false && strpos($url, 'secure.gravatar.com') === false) {
            return $url;
        }

        $size = isset($args['size']) ? (int) $args['size'] : 96;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $size . ' ' . $size . '" width="' . $size . '" height="' . $size . '">'
             . '<rect fill="#ddd" width="' . $size . '" height="' . $size . '"/>'
             . '<text x="50%" y="50%" font-size="' . ($size / 2) . '" fill="#999" text-anchor="middle" dy=".35em">?</text></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function stripWcBloat() {
        add_filter('woocommerce_allow_marketplace_suggestions', '__return_false');
        add_filter('woocommerce_show_marketplace_suggestions', '__return_false');

        add_action('admin_init', function () {
            if (class_exists('Automattic\WooCommerce\Internal\Admin\AnalyticsOverview')) {
                remove_action('admin_menu', ['Automattic\WooCommerce\Admin\Features\Marketing', 'register_pages']);
            }
            remove_action('admin_notices', 'woothemes_updater_notice');
        });

        add_filter('woocommerce_admin_disabled', '__return_true');
    }
}
