<?php
defined('ABSPATH') || exit;

final class RFC_Pro_Installer {

    private $api_base = 'https://manage.shahfahad.info/api/v1/';
    private $pro_dir;

    public function __construct() {
        $this->pro_dir = RFC_PATH . 'pro/';

        add_action('wp_ajax_rfc_install_pro', [$this, 'ajaxInstallPro']);
        add_action('wp_ajax_rfc_remove_pro', [$this, 'ajaxRemovePro']);
    }

    public function downloadAndInstall($download_url) {
        if (!current_user_can('install_plugins')) {
            return new \WP_Error('permission', 'Insufficient permissions to install plugins.');
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        if (!$wp_filesystem) {
            return new \WP_Error('filesystem', 'Could not initialize WordPress filesystem.');
        }

        RFC_Crash_Guard::takeSnapshot();

        $tmp_file = download_url($download_url, 60);
        if (is_wp_error($tmp_file)) {
            return $tmp_file;
        }

        $tmp_dir = get_temp_dir() . 'rfc_pro_' . wp_generate_password(8, false) . '/';
        $result = unzip_file($tmp_file, $tmp_dir);
        @unlink($tmp_file);

        if (is_wp_error($result)) {
            $wp_filesystem->delete($tmp_dir, true);
            return $result;
        }

        $source = $tmp_dir;
        $dirs = glob($tmp_dir . '*', GLOB_ONLYDIR);
        if (count($dirs) === 1) {
            $source = $dirs[0] . '/';
        }

        if (!file_exists($source . 'class-rfc-pro-loader.php')) {
            $pro_subdir = $source . 'pro/';
            if (file_exists($pro_subdir . 'class-rfc-pro-loader.php')) {
                $source = $pro_subdir;
            } else {
                $wp_filesystem->delete($tmp_dir, true);
                return new \WP_Error('invalid_package', 'Invalid Pro package: missing core files.');
            }
        }

        if (is_dir($this->pro_dir)) {
            $wp_filesystem->delete($this->pro_dir, true);
        }

        $copy_result = copy_dir($source, $this->pro_dir);
        $wp_filesystem->delete($tmp_dir, true);

        if (is_wp_error($copy_result)) {
            return $copy_result;
        }

        if (!file_exists($this->pro_dir . 'class-rfc-pro-loader.php')) {
            return new \WP_Error('install_failed', 'Pro files were not installed correctly.');
        }

        update_option('rfc_pro_installed_at', time());
        update_option('rfc_pro_version', RFC_VERSION);

        do_action('rfc_pro_installed');

        return true;
    }

    public function installFromLicense($license_key) {
        $response = wp_remote_post($this->api_base . 'license/activate', [
            'timeout' => 30,
            'body'    => wp_json_encode([
                'license_key'    => $license_key,
                'domain'         => home_url(),
                'plugin_slug'    => 'rocketfuel-cache',
                'plugin_version' => RFC_VERSION,
                'action'         => 'activate_and_download',
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Plugin'     => 'rocketfuel-cache',
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['success'])) {
            return new \WP_Error(
                'activation_failed',
                $body['message'] ?? 'License activation failed.'
            );
        }

        if (empty($body['download_url'])) {
            return new \WP_Error('no_download', 'No download URL provided by server.');
        }

        return $this->downloadAndInstall($body['download_url']);
    }

    public function installFromTrial($email) {
        $response = wp_remote_post($this->api_base . 'trial/start', [
            'timeout' => 30,
            'body'    => wp_json_encode([
                'email'          => $email,
                'domain'         => home_url(),
                'plugin_slug'    => 'rocketfuel-cache',
                'plugin_version' => RFC_VERSION,
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Plugin'     => 'rocketfuel-cache',
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['success'])) {
            return new \WP_Error(
                'trial_failed',
                $body['message'] ?? 'Trial activation failed.'
            );
        }

        if (!empty($body['license_key'])) {
            update_option('rfc_trial_key', $body['license_key']);
        }
        if (!empty($body['trial_ends_at'])) {
            update_option('rfc_trial_ends_at', $body['trial_ends_at']);
        }

        if (!empty($body['download_url'])) {
            return $this->downloadAndInstall($body['download_url']);
        }

        return true;
    }

    public function removePro() {
        if (!current_user_can('install_plugins')) {
            return new \WP_Error('permission', 'Insufficient permissions.');
        }

        if (!is_dir($this->pro_dir)) {
            return true;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        $result = $wp_filesystem->delete($this->pro_dir, true);

        if ($result) {
            delete_option('rfc_pro_installed_at');
            delete_option('rfc_pro_version');
            do_action('rfc_pro_removed');
        }

        return $result;
    }

    public function isProInstalled() {
        return is_dir($this->pro_dir) && file_exists($this->pro_dir . 'class-rfc-pro-loader.php');
    }

    public function ajaxInstallPro() {
        check_ajax_referer('rfc_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $method = sanitize_text_field($_POST['method'] ?? '');
        $result = false;

        if ($method === 'license') {
            $key = sanitize_text_field($_POST['license_key'] ?? '');
            if (empty($key)) {
                wp_send_json_error(['message' => 'Please enter a license key.']);
            }
            $result = $this->installFromLicense($key);
        } elseif ($method === 'trial') {
            $email = sanitize_email($_POST['email'] ?? '');
            if (empty($email) || !is_email($email)) {
                wp_send_json_error(['message' => 'Please enter a valid email address.']);
            }
            $result = $this->installFromTrial($email);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        if ($result === true) {
            wp_send_json_success([
                'message' => 'Pro features have been installed and activated!',
                'reload'  => true,
            ]);
        }

        wp_send_json_error(['message' => 'Installation failed. Please try again or contact support.']);
    }

    public function ajaxRemovePro() {
        check_ajax_referer('rfc_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $result = $this->removePro();
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Pro features have been removed.', 'reload' => true]);
    }
}
