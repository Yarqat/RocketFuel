<?php defined('ABSPATH') || exit; ?>

<div class="rfc-settings-page">

    <div class="rfc-card">
        <h3><?php esc_html_e('Import / Export Settings', 'rocketfuel-cache'); ?></h3>

        <div class="rfc-tools-row">
            <div class="rfc-tools-col">
                <h4><?php esc_html_e('Export', 'rocketfuel-cache'); ?></h4>
                <p><?php esc_html_e('Download your current settings as a JSON file.', 'rocketfuel-cache'); ?></p>
                <button type="button" id="rfc-export-btn" class="rfc-btn"><?php esc_html_e('Export Settings', 'rocketfuel-cache'); ?></button>
            </div>
            <div class="rfc-tools-col">
                <h4><?php esc_html_e('Import', 'rocketfuel-cache'); ?></h4>
                <p><?php esc_html_e('Upload a previously exported JSON settings file.', 'rocketfuel-cache'); ?></p>
                <form id="rfc-import-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('rfc_import_settings', '_rfc_import_nonce'); ?>
                    <input type="file" id="rfc-import-file" name="rfc_import_file" accept=".json" class="rfc-file-input">
                    <button type="button" id="rfc-import-btn" class="rfc-btn"><?php esc_html_e('Import Settings', 'rocketfuel-cache'); ?></button>
                </form>
            </div>
        </div>
    </div>

    <div class="rfc-card">
        <h3><?php esc_html_e('Safe Mode', 'rocketfuel-cache'); ?></h3>
        <form method="post" action="">
            <?php wp_nonce_field('rfc_save_settings', '_rfc_nonce'); ?>
            <input type="hidden" name="rfc_tab" value="tools">

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[safe_mode]" value="1" <?php checked($settings->get('safe_mode')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Enable Safe Mode', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-notice-inline rfc-notice-warning">
                <p><?php esc_html_e('Safe Mode disables all caching and optimization features. Use this to troubleshoot issues without deactivating the plugin.', 'rocketfuel-cache'); ?></p>
            </div>

            <button type="submit" name="rfc_save_settings" class="rfc-btn"><?php esc_html_e('Save', 'rocketfuel-cache'); ?></button>
        </form>
    </div>

    <div class="rfc-card">
        <h3><?php esc_html_e('Debug Log', 'rocketfuel-cache'); ?></h3>
        <form method="post" action="">
            <?php wp_nonce_field('rfc_save_settings', '_rfc_nonce'); ?>
            <input type="hidden" name="rfc_tab" value="tools">

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[debug_log]" value="1" <?php checked($settings->get('debug_log')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Enable Debug Logging', 'rocketfuel-cache'); ?></span>
            </div>

            <button type="submit" name="rfc_save_settings" class="rfc-btn"><?php esc_html_e('Save', 'rocketfuel-cache'); ?></button>
        </form>

        <?php
        $log_file = WP_CONTENT_DIR . '/cache/rocketfuel/debug.log';
        $log_content = '';
        if (file_exists($log_file)) {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines) {
                $lines = array_slice($lines, -500);
                $log_content = implode("\n", $lines);
            }
        }
        ?>
        <div class="rfc-field" style="margin-top: 1rem;">
            <label class="rfc-field-label"><?php esc_html_e('Log Output', 'rocketfuel-cache'); ?></label>
            <textarea class="rfc-textarea rfc-log-viewer" rows="15" readonly><?php echo esc_textarea($log_content); ?></textarea>
        </div>
    </div>

    <div class="rfc-card">
        <h3><?php esc_html_e('Server Info', 'rocketfuel-cache'); ?></h3>

        <?php
        $server_info = [
            __('PHP Version', 'rocketfuel-cache')        => phpversion(),
            __('Server Software', 'rocketfuel-cache')     => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field($_SERVER['SERVER_SOFTWARE']) : __('Unknown', 'rocketfuel-cache'),
            __('WordPress Version', 'rocketfuel-cache')   => get_bloginfo('version'),
            __('Memory Limit', 'rocketfuel-cache')        => ini_get('memory_limit'),
            __('Max Execution Time', 'rocketfuel-cache')  => ini_get('max_execution_time') . 's',
            __('Upload Max Filesize', 'rocketfuel-cache')  => ini_get('upload_max_filesize'),
            __('Multisite', 'rocketfuel-cache')            => is_multisite() ? __('Yes', 'rocketfuel-cache') : __('No', 'rocketfuel-cache'),
            __('OPcache', 'rocketfuel-cache')              => function_exists('opcache_get_status') ? __('Enabled', 'rocketfuel-cache') : __('Disabled', 'rocketfuel-cache'),
            __('cURL', 'rocketfuel-cache')                 => function_exists('curl_version') ? curl_version()['version'] : __('Not available', 'rocketfuel-cache'),
            __('WP_CACHE', 'rocketfuel-cache')             => defined('WP_CACHE') && WP_CACHE ? __('Defined (true)', 'rocketfuel-cache') : __('Not defined', 'rocketfuel-cache'),
            __('Cache Directory', 'rocketfuel-cache')      => RFC_CACHE_DIR,
            __('Cache Dir Writable', 'rocketfuel-cache')   => wp_is_writable(WP_CONTENT_DIR . '/cache/') ? __('Yes', 'rocketfuel-cache') : __('No', 'rocketfuel-cache'),
        ];
        ?>

        <table class="rfc-info-table">
            <?php foreach ($server_info as $label => $value) : ?>
                <tr>
                    <th><?php echo esc_html($label); ?></th>
                    <td><?php echo esc_html($value); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="rfc-card rfc-card-danger">
        <h3><?php esc_html_e('Reset All Settings', 'rocketfuel-cache'); ?></h3>
        <p><?php esc_html_e('This will restore all settings to their defaults. This action cannot be undone.', 'rocketfuel-cache'); ?></p>
        <button type="button" id="rfc-reset-btn" class="rfc-btn rfc-btn-danger"><?php esc_html_e('Reset All Settings', 'rocketfuel-cache'); ?></button>
    </div>

</div>
