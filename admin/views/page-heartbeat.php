<?php defined('ABSPATH') || exit; ?>

<div class="rfc-settings-page">
    <form method="post" action="">
        <?php wp_nonce_field('rfc_save_settings', '_rfc_nonce'); ?>
        <input type="hidden" name="rfc_tab" value="heartbeat">

        <div class="rfc-card">
            <h3><?php esc_html_e('Heartbeat Control', 'rocketfuel-cache'); ?></h3>
            <p class="rfc-card-desc"><?php esc_html_e('The WordPress Heartbeat API sends AJAX requests every 15-60 seconds. Reducing or disabling it can lower server load.', 'rocketfuel-cache'); ?></p>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-heartbeat-dashboard"><?php esc_html_e('Dashboard', 'rocketfuel-cache'); ?></label>
                <select id="rfc-heartbeat-dashboard" name="rfc[heartbeat_dashboard]" class="rfc-select">
                    <option value="allow" <?php selected($settings->get('heartbeat_dashboard'), 'allow'); ?>><?php esc_html_e('Allow', 'rocketfuel-cache'); ?></option>
                    <option value="reduce" <?php selected($settings->get('heartbeat_dashboard'), 'reduce'); ?>><?php esc_html_e('Reduce', 'rocketfuel-cache'); ?></option>
                    <option value="disable" <?php selected($settings->get('heartbeat_dashboard'), 'disable'); ?>><?php esc_html_e('Disable', 'rocketfuel-cache'); ?></option>
                </select>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-heartbeat-editor"><?php esc_html_e('Post Editor', 'rocketfuel-cache'); ?></label>
                <select id="rfc-heartbeat-editor" name="rfc[heartbeat_editor]" class="rfc-select">
                    <option value="allow" <?php selected($settings->get('heartbeat_editor'), 'allow'); ?>><?php esc_html_e('Allow', 'rocketfuel-cache'); ?></option>
                    <option value="reduce" <?php selected($settings->get('heartbeat_editor'), 'reduce'); ?>><?php esc_html_e('Reduce', 'rocketfuel-cache'); ?></option>
                    <option value="disable" <?php selected($settings->get('heartbeat_editor'), 'disable'); ?>><?php esc_html_e('Disable', 'rocketfuel-cache'); ?></option>
                </select>
                <p class="rfc-field-desc"><?php esc_html_e('Warning: Disabling the heartbeat in the editor will disable auto-save.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-heartbeat-frontend"><?php esc_html_e('Frontend', 'rocketfuel-cache'); ?></label>
                <select id="rfc-heartbeat-frontend" name="rfc[heartbeat_frontend]" class="rfc-select">
                    <option value="allow" <?php selected($settings->get('heartbeat_frontend'), 'allow'); ?>><?php esc_html_e('Allow', 'rocketfuel-cache'); ?></option>
                    <option value="reduce" <?php selected($settings->get('heartbeat_frontend'), 'reduce'); ?>><?php esc_html_e('Reduce', 'rocketfuel-cache'); ?></option>
                    <option value="disable" <?php selected($settings->get('heartbeat_frontend'), 'disable'); ?>><?php esc_html_e('Disable', 'rocketfuel-cache'); ?></option>
                </select>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-heartbeat-frequency"><?php esc_html_e('Custom Frequency (seconds)', 'rocketfuel-cache'); ?></label>
                <input type="number" id="rfc-heartbeat-frequency" name="rfc[heartbeat_frequency]" value="<?php echo esc_attr($settings->get('heartbeat_frequency')); ?>" min="15" max="300" step="1" class="rfc-input-number">
                <p class="rfc-field-desc"><?php esc_html_e('How often the heartbeat sends requests when set to "Reduce". Minimum 15 seconds.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-submit-row">
            <button type="submit" name="rfc_save_settings" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Save Changes', 'rocketfuel-cache'); ?></button>
        </div>
    </form>
</div>
