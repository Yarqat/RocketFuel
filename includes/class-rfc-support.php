<?php
defined('ABSPATH') || exit;

class RFC_Support {

    private $settings;
    private $api_base = 'https://manage.shahfahad.info/api/v1/support';

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;
        add_action('wp_ajax_rfc_create_ticket', [$this, 'ajaxCreateTicket']);
        add_action('wp_ajax_rfc_get_ticket', [$this, 'ajaxGetTicket']);
        add_action('wp_ajax_rfc_reply_ticket', [$this, 'ajaxReplyTicket']);
        add_action('wp_ajax_rfc_get_tickets', [$this, 'ajaxGetTickets']);
    }

    public function createTicket($subject, $message, $priority, $email) {
        $system_info = $this->getSystemInfo();

        $payload = [
            'domain'      => home_url(),
            'email'       => $email,
            'subject'     => $subject,
            'message'     => $message,
            'priority'    => $priority,
            'system_info' => $system_info,
        ];

        $license_key = $this->settings->get('license_key', '');
        if (!empty($license_key)) {
            $payload['license_key'] = $license_key;
        }

        $response = wp_remote_post($this->api_base . '/create', [
            'timeout' => 30,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['ticket_id'])) {
            $tickets = get_option('rfc_tickets', []);
            $tickets[] = $body['ticket_id'];
            update_option('rfc_tickets', $tickets, false);
        }

        return $body;
    }

    public function getTicket($ticket_id) {
        $url = add_query_arg([
            'domain' => home_url(),
        ], $this->api_base . '/ticket/' . rawurlencode($ticket_id));

        $response = wp_remote_get($url, [
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function replyToTicket($ticket_id, $message) {
        $payload = [
            'domain'    => home_url(),
            'ticket_id' => $ticket_id,
            'message'   => $message,
        ];

        $license_key = $this->settings->get('license_key', '');
        if (!empty($license_key)) {
            $payload['license_key'] = $license_key;
        }

        $response = wp_remote_post($this->api_base . '/reply', [
            'timeout' => 30,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function getMyTickets() {
        $args = [
            'domain' => home_url(),
        ];

        $license_key = $this->settings->get('license_key', '');
        if (!empty($license_key)) {
            $args['license_key'] = $license_key;
        }

        $url = add_query_arg($args, $this->api_base . '/tickets');

        $response = wp_remote_get($url, [
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function getSystemInfo() {
        global $wp_version;

        $theme = wp_get_theme();
        $plugins = get_option('active_plugins', []);
        $plugin_list = [];
        foreach ($plugins as $plugin_file) {
            $data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file, false, false);
            $plugin_list[] = $data['Name'] . ' ' . $data['Version'];
        }

        $settings_summary = [];
        $keys = [
            'page_cache_enabled', 'minify_html', 'minify_css', 'minify_js',
            'defer_js', 'lazy_load_images', 'local_google_fonts', 'cdn_enabled',
            'disable_emojis', 'remove_query_strings', 'safe_mode',
        ];
        foreach ($keys as $key) {
            $settings_summary[$key] = $this->settings->get($key, false);
        }

        return [
            'domain'             => home_url(),
            'wp_version'         => $wp_version,
            'php_version'        => PHP_VERSION,
            'server_software'    => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field($_SERVER['SERVER_SOFTWARE']) : 'Unknown',
            'active_theme'       => $theme->get('Name') . ' ' . $theme->get('Version'),
            'active_plugins'     => $plugin_list,
            'rocketfuel_version' => RFC_VERSION,
            'rocketfuel_settings' => $settings_summary,
            'memory_limit'       => ini_get('memory_limit'),
            'is_multisite'       => is_multisite(),
        ];
    }

    public function ajaxCreateTicket() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $subject  = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
        $message  = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $priority = isset($_POST['priority']) ? sanitize_key($_POST['priority']) : 'normal';
        $email    = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (empty($subject) || empty($message) || empty($email)) {
            wp_send_json_error('Please fill in all required fields');
        }

        $allowed_priorities = ['low', 'normal', 'high', 'urgent'];
        if (!in_array($priority, $allowed_priorities, true)) {
            $priority = 'normal';
        }

        $result = $this->createTicket($subject, $message, $priority, $email);

        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success($result);
    }

    public function ajaxGetTicket() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $ticket_id = isset($_GET['ticket_id']) ? sanitize_text_field($_GET['ticket_id']) : '';

        if (empty($ticket_id)) {
            wp_send_json_error('Missing ticket ID');
        }

        $result = $this->getTicket($ticket_id);

        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success($result);
    }

    public function ajaxReplyTicket() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $ticket_id = isset($_POST['ticket_id']) ? sanitize_text_field($_POST['ticket_id']) : '';
        $message   = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

        if (empty($ticket_id) || empty($message)) {
            wp_send_json_error('Missing required fields');
        }

        $result = $this->replyToTicket($ticket_id, $message);

        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success($result);
    }

    public function ajaxGetTickets() {
        check_ajax_referer('rfc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $result = $this->getMyTickets();

        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success($result);
    }
}
