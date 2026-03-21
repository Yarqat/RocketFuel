<?php
defined('ABSPATH') || exit;

final class RFC_Cache_Purge {

    private $settings;

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        if ($this->settings->isSafeMode()) return;

        add_action('save_post', [$this, 'onPostSave'], 10, 2);
        add_action('wp_trash_post', [$this, 'onPostTrash']);
        add_action('delete_post', [$this, 'onPostTrash']);
        add_action('comment_post', [$this, 'onComment'], 10, 2);
        add_action('edit_comment', [$this, 'onCommentEdit']);
        add_action('transition_comment_status', [$this, 'onCommentTransition'], 10, 3);
        add_action('switch_theme', [$this, 'purgeAll']);
        add_action('customize_save_after', [$this, 'purgeAll']);
        add_action('update_option_sidebars_widgets', [$this, 'purgeAll']);
        add_action('activated_plugin', [$this, 'purgeAll']);
        add_action('deactivated_plugin', [$this, 'purgeAll']);
        add_action('upgrader_process_complete', [$this, 'purgeAll']);

        if (class_exists('WooCommerce')) {
            add_action('woocommerce_product_set_stock', [$this, 'onWcStock']);
            add_action('woocommerce_product_set_stock_status', [$this, 'onWcStockStatus'], 10, 3);
            add_action('woocommerce_variation_set_stock', [$this, 'onWcStock']);
        }

        add_action('wp_ajax_rfc_purge_all', [$this, 'ajaxPurgeAll']);
        add_action('wp_ajax_rfc_purge_url', [$this, 'ajaxPurgeUrl']);
    }

    public function onPostSave($post_id, $post) {
        if (!$this->settings->get('purge_on_post_update', true)) return;
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
        if ($post->post_status !== 'publish') return;

        $this->purgePost($post_id);
    }

    public function onPostTrash($post_id) {
        $this->purgePost($post_id);
    }

    public function onComment($comment_id, $approved) {
        if ($approved !== 1) return;
        $comment = get_comment($comment_id);
        if ($comment) {
            $this->purgePost($comment->comment_post_ID);
        }
    }

    public function onCommentEdit($comment_id) {
        $comment = get_comment($comment_id);
        if ($comment) {
            $this->purgePost($comment->comment_post_ID);
        }
    }

    public function onCommentTransition($new, $old, $comment) {
        $this->purgePost($comment->comment_post_ID);
    }

    public function onWcStock($product) {
        if (is_numeric($product)) {
            $product = wc_get_product($product);
        }
        if ($product) {
            $this->purgePost($product->get_id());
        }
    }

    public function onWcStockStatus($product_id, $status, $product) {
        $this->purgePost($product_id);
    }

    public function purgePost($post_id) {
        $url = get_permalink($post_id);
        if (!$url) return;

        $urls = [$url];

        if ($this->settings->get('purge_homepage', true)) {
            $urls[] = home_url('/');
            $urls[] = get_post_type_archive_link(get_post_type($post_id));
        }

        if ($this->settings->get('purge_archives', true)) {
            $cats = get_the_category($post_id);
            if ($cats) {
                foreach ($cats as $cat) {
                    $urls[] = get_category_link($cat->term_id);
                }
            }

            $tags = get_the_tags($post_id);
            if ($tags) {
                foreach ($tags as $tag) {
                    $urls[] = get_tag_link($tag->term_id);
                }
            }

            $author_id = get_post_field('post_author', $post_id);
            if ($author_id) {
                $urls[] = get_author_posts_url($author_id);
            }
        }

        $urls = apply_filters('rfc_purge_post_related_urls', array_filter(array_unique($urls)), $post_id);

        foreach ($urls as $u) {
            $this->purgeUrl($u);
        }

        do_action('rfc_cache_cleared_post', $post_id);
    }

    public function purgeUrl($url) {
        $parsed = wp_parse_url($url);
        if (!$parsed || empty($parsed['host'])) return;

        $host = sanitize_file_name($parsed['host']);
        $path = trim($parsed['path'] ?? '/', '/');
        $path = $path === '' ? 'index' : $path;
        $parts = array_map('sanitize_file_name', explode('/', $path));

        $base = RFC_CACHE_DIR . $host . '/' . implode('/', $parts) . '/';

        foreach (['index-https.html', 'index-http.html'] as $file) {
            $full = $base . $file;
            if (file_exists($full)) @unlink($full);
            if (file_exists($full . '.gz')) @unlink($full . '.gz');
            if (file_exists($full . '.br')) @unlink($full . '.br');
        }

        do_action('rfc_cache_cleared_url', $url);
    }

    public function purgeAll() {
        RFC_Page_Cache::flush();
        $this->purgeMinified();
    }

    public function purgeMinified() {
        $dirs = [
            RFC_MIN_DIR . 'css/',
            RFC_MIN_DIR . 'js/',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            $files = glob($dir . '*');
            if ($files) {
                foreach ($files as $f) {
                    if (is_file($f)) @unlink($f);
                }
            }
        }
    }

    public function ajaxPurgeAll() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        $this->purgeAll();
        wp_send_json_success(['message' => 'Cache cleared']);
    }

    public function ajaxPurgeUrl() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
        if ($url) {
            $this->purgeUrl($url);
            wp_send_json_success(['message' => 'URL cache cleared']);
        }
        wp_send_json_error('No URL provided');
    }
}
