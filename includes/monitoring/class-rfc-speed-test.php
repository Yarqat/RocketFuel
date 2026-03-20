<?php
defined('ABSPATH') || exit;

class RFC_Speed_Test {

    private $settings;
    private $api_base = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
    private $sync_url = 'https://manage.shahfahad.info/api/v1/report/speed';

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;
        add_action('rfc_run_baseline_test', [$this, 'storeBaseline']);
        add_action('rfc_speed_test_weekly', [$this, 'scheduledTest']);
        add_action('wp_ajax_rfc_run_speed_test', [$this, 'ajaxRunTest']);
        add_action('wp_ajax_rfc_sync_report', [$this, 'ajaxSyncReport']);
    }

    public function scheduleBaseline() {
        if (get_option('rfc_baseline_report')) {
            return;
        }
        wp_schedule_single_event(time() + 30, 'rfc_run_baseline_test');
    }

    public function scheduleWeekly() {
        if (!wp_next_scheduled('rfc_speed_test_weekly')) {
            wp_schedule_event(time(), 'weekly', 'rfc_speed_test_weekly');
        }
    }

    public function runTest($url, $strategy = 'mobile') {
        $request_url = add_query_arg([
            'url'      => rawurlencode($url),
            'strategy' => $strategy,
        ], $this->api_base);

        $response = wp_remote_get($request_url, [
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['lighthouseResult'])) {
            return ['error' => 'Invalid API response'];
        }

        $lhr = $body['lighthouseResult'];
        $categories = $lhr['categories'] ?? [];
        $audits = $lhr['audits'] ?? [];

        return [
            'url'              => $url,
            'strategy'         => $strategy,
            'performance'      => isset($categories['performance']['score']) ? (int) round($categories['performance']['score'] * 100) : 0,
            'lcp'              => $this->extractMetric($audits, 'largest-contentful-paint'),
            'fid'              => $this->extractMetric($audits, 'max-potential-fid'),
            'inp'              => $this->extractMetric($audits, 'interaction-to-next-paint'),
            'cls'              => $this->extractMetric($audits, 'cumulative-layout-shift'),
            'ttfb'             => $this->extractMetric($audits, 'server-response-time'),
            'total_byte_weight' => isset($audits['total-byte-weight']['numericValue']) ? (int) $audits['total-byte-weight']['numericValue'] : 0,
            'request_count'    => isset($audits['network-requests']['details']['items']) ? count($audits['network-requests']['details']['items']) : 0,
            'tested_at'        => current_time('mysql'),
            'timestamp'        => time(),
        ];
    }

    private function extractMetric($audits, $key) {
        if (!isset($audits[$key])) {
            return null;
        }
        return [
            'value'   => $audits[$key]['numericValue'] ?? 0,
            'display' => $audits[$key]['displayValue'] ?? '',
            'score'   => isset($audits[$key]['score']) ? (int) round($audits[$key]['score'] * 100) : 0,
        ];
    }

    public function storeBaseline() {
        $url = home_url('/');

        $mobile = $this->runTest($url, 'mobile');
        $desktop = $this->runTest($url, 'desktop');

        if (isset($mobile['error']) || isset($desktop['error'])) {
            return false;
        }

        $baseline = [
            'mobile'  => $mobile,
            'desktop' => $desktop,
        ];

        update_option('rfc_baseline_report', $baseline, false);
        update_option('rfc_activated_at', current_time('mysql'), false);

        $this->storeCurrent();

        return true;
    }

    public function storeCurrent() {
        $url = home_url('/');

        $mobile = $this->runTest($url, 'mobile');
        $desktop = $this->runTest($url, 'desktop');

        if (isset($mobile['error']) || isset($desktop['error'])) {
            return false;
        }

        $current = [
            'mobile'  => $mobile,
            'desktop' => $desktop,
        ];

        update_option('rfc_current_report', $current, false);

        return true;
    }

    public function getReport() {
        $baseline = get_option('rfc_baseline_report', []);
        $current = get_option('rfc_current_report', []);

        return [
            'baseline'     => $baseline,
            'current'      => $current,
            'improvements' => $this->getImprovement($baseline, $current),
        ];
    }

    public function getImprovement($baseline = null, $current = null) {
        if ($baseline === null) {
            $baseline = get_option('rfc_baseline_report', []);
        }
        if ($current === null) {
            $current = get_option('rfc_current_report', []);
        }

        if (empty($baseline) || empty($current)) {
            return [];
        }

        $improvements = [];
        foreach (['mobile', 'desktop'] as $strategy) {
            if (!isset($baseline[$strategy]) || !isset($current[$strategy])) {
                continue;
            }

            $b = $baseline[$strategy];
            $c = $current[$strategy];

            $improvements[$strategy] = [
                'performance' => $this->calcImprovement($b['performance'] ?? 0, $c['performance'] ?? 0),
                'lcp'         => $this->calcImprovement($b['lcp']['value'] ?? 0, $c['lcp']['value'] ?? 0, true),
                'cls'         => $this->calcImprovement($b['cls']['value'] ?? 0, $c['cls']['value'] ?? 0, true),
                'ttfb'        => $this->calcImprovement($b['ttfb']['value'] ?? 0, $c['ttfb']['value'] ?? 0, true),
            ];
        }

        return $improvements;
    }

    private function calcImprovement($before, $after, $lower_is_better = false) {
        if ($before == 0) {
            return 0;
        }

        $change = (($after - $before) / $before) * 100;

        if ($lower_is_better) {
            $change = -$change;
        }

        return round($change, 1);
    }

    public function scheduledTest() {
        $this->storeCurrent();

        $current = get_option('rfc_current_report', []);
        if (empty($current)) {
            return;
        }

        $history = get_option('rfc_report_history', []);
        $history[] = [
            'date'    => current_time('mysql'),
            'mobile'  => $current['mobile']['performance'] ?? 0,
            'desktop' => $current['desktop']['performance'] ?? 0,
        ];

        if (count($history) > 12) {
            $history = array_slice($history, -12);
        }

        update_option('rfc_report_history', $history, false);
    }

    public function syncToServer() {
        $license_key = $this->settings->get('license_key', '');
        if (empty($license_key)) {
            return ['error' => 'No license key'];
        }

        $report = $this->getReport();

        $payload = [
            'domain'          => home_url(),
            'license_key'     => $license_key,
            'baseline'        => $report['baseline'],
            'current'         => $report['current'],
            'improvements'    => $report['improvements'],
            'plugin_version'  => RFC_VERSION,
            'active_features' => $this->getActiveFeatures(),
        ];

        $response = wp_remote_post($this->sync_url, [
            'timeout' => 30,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private function getActiveFeatures() {
        $features = [];
        $checks = [
            'page_cache_enabled', 'minify_html', 'minify_css', 'minify_js',
            'defer_js', 'lazy_load_images', 'local_google_fonts', 'disable_emojis',
            'cdn_enabled', 'remove_query_strings',
        ];

        foreach ($checks as $key) {
            if ($this->settings->get($key, false)) {
                $features[] = $key;
            }
        }

        return $features;
    }

    public function getPageStats() {
        $url = home_url('/');

        $response = wp_remote_get($url, [
            'timeout'   => 15,
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $headers = wp_remote_retrieve_headers($response);
        $body = wp_remote_retrieve_body($response);

        $gzip = false;
        if (isset($headers['content-encoding'])) {
            $gzip = strpos($headers['content-encoding'], 'gzip') !== false;
        }

        return [
            'page_size'     => strlen($body),
            'gzip_enabled'  => $gzip,
            'content_type'  => $headers['content-type'] ?? '',
        ];
    }

    public function getScoreColor($score) {
        if ($score >= 90) {
            return 'green';
        }
        if ($score >= 50) {
            return 'orange';
        }
        return 'red';
    }

    public function ajaxRunTest() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $this->storeCurrent();
        $report = $this->getReport();

        wp_send_json_success($report);
    }

    public function ajaxSyncReport() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $result = $this->syncToServer();

        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success($result);
    }
}
