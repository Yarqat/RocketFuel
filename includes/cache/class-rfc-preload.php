<?php
defined('ABSPATH') || exit;

final class RFC_Preload {

    private $settings;
    private $batch_size = 10;

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;
        add_action('rfc_preload_event', [$this, 'processBatch']);
        add_action('rfc_preload_single', [$this, 'warmUrl']);
        add_action('publish_post', [$this, 'onPublish'], 99);
        add_action('publish_page', [$this, 'onPublish'], 99);
    }

    public function start() {
        if (!$this->settings->get('preload_enabled', true)) return;

        $urls = $this->collectUrls();
        if (empty($urls)) return;

        update_option('rfc_preload_queue', $urls, false);
        update_option('rfc_preload_total', count($urls), false);
        update_option('rfc_preload_started', time(), false);

        do_action('rfc_preload_started', count($urls));

        if (!wp_next_scheduled('rfc_preload_event')) {
            wp_schedule_single_event(time() + 5, 'rfc_preload_event');
        }
    }

    public function processBatch() {
        $queue = get_option('rfc_preload_queue', []);
        if (empty($queue)) {
            delete_option('rfc_preload_queue');
            delete_option('rfc_preload_total');
            delete_option('rfc_preload_started');
            do_action('rfc_preload_completed', 0);
            return;
        }

        $rate = $this->settings->get('preload_rate', 'normal');
        $this->batch_size = $rate === 'slow' ? 5 : ($rate === 'fast' ? 20 : 10);

        $batch = array_splice($queue, 0, $this->batch_size);
        update_option('rfc_preload_queue', $queue, false);

        foreach ($batch as $url) {
            $this->warmUrl($url);

            if ($rate === 'slow') {
                usleep(500000);
            } elseif ($rate === 'normal') {
                usleep(250000);
            }
        }

        if (!empty($queue)) {
            wp_schedule_single_event(time() + 2, 'rfc_preload_event');
        } else {
            delete_option('rfc_preload_queue');
            $total = get_option('rfc_preload_total', 0);
            delete_option('rfc_preload_total');
            delete_option('rfc_preload_started');
            do_action('rfc_preload_completed', $total);
        }
    }

    public function warmUrl($url) {
        if (!is_string($url) || empty($url)) return;

        wp_remote_get($url, [
            'timeout'    => 10,
            'blocking'   => true,
            'sslverify'  => false,
            'user-agent' => 'RocketFuel-Preload',
            'headers'    => ['X-RocketFuel-Preload' => '1'],
        ]);
    }

    public function onPublish($post_id) {
        $url = get_permalink($post_id);
        if ($url) {
            wp_schedule_single_event(time() + 3, 'rfc_preload_single', [$url]);
        }
    }

    private function collectUrls() {
        $urls = [];

        $urls[] = home_url('/');

        $sitemap_url = $this->settings->get('preload_sitemap_url', '');
        if (empty($sitemap_url)) {
            $sitemap_url = $this->detectSitemap();
        }

        if ($sitemap_url) {
            $parsed = $this->parseSitemap($sitemap_url);
            $urls = array_merge($urls, $parsed);
        } else {
            $posts = get_posts([
                'post_type'      => ['post', 'page'],
                'post_status'    => 'publish',
                'posts_per_page' => 500,
                'fields'         => 'ids',
                'orderby'        => 'modified',
                'order'          => 'DESC',
            ]);

            foreach ($posts as $pid) {
                $urls[] = get_permalink($pid);
            }
        }

        return apply_filters('rfc_preload_urls', array_unique(array_filter($urls)));
    }

    private function detectSitemap() {
        $candidates = [
            home_url('/sitemap_index.xml'),
            home_url('/sitemap.xml'),
            home_url('/wp-sitemap.xml'),
            home_url('/sitemap_index.xml'),
        ];

        foreach ($candidates as $url) {
            $response = wp_remote_head($url, ['timeout' => 5, 'sslverify' => false]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                return $url;
            }
        }

        return '';
    }

    private function parseSitemap($url, $depth = 0) {
        if ($depth > 3) return [];

        $response = wp_remote_get($url, ['timeout' => 15, 'sslverify' => false]);
        if (is_wp_error($response)) return [];

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) return [];

        $urls = [];

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        if ($xml === false) return [];

        if (isset($xml->sitemap)) {
            foreach ($xml->sitemap as $entry) {
                $loc = (string) ($entry->loc ?? '');
                if ($loc) {
                    $urls = array_merge($urls, $this->parseSitemap($loc, $depth + 1));
                }
            }
        }

        if (isset($xml->url)) {
            foreach ($xml->url as $entry) {
                $loc = (string) ($entry->loc ?? '');
                if ($loc) {
                    $urls[] = $loc;
                }
            }
        }

        return $urls;
    }

    public function getProgress() {
        $queue = get_option('rfc_preload_queue', []);
        $total = get_option('rfc_preload_total', 0);
        $remaining = is_array($queue) ? count($queue) : 0;
        return [
            'total'     => $total,
            'remaining' => $remaining,
            'done'      => $total - $remaining,
            'active'    => $total > 0 && $remaining > 0,
        ];
    }
}
