<?php defined('ABSPATH') || exit; ?>

<div class="rfc-settings-page">
    <form method="post" action="">
        <?php wp_nonce_field('rfc_save_settings', '_rfc_nonce'); ?>
        <input type="hidden" name="rfc_tab" value="security">

        <div class="rfc-card">
            <h3><?php esc_html_e('Security Hardening', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[disable_file_editor]" value="1" <?php checked($settings->get('disable_file_editor')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Disable File Editor', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Disables the built-in theme and plugin editor in the WordPress admin.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[disable_directory_browsing]" value="1" <?php checked($settings->get('disable_directory_browsing')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Disable Directory Browsing', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Prevents visitors from viewing the contents of directories on your server.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[block_author_scans]" value="1" <?php checked($settings->get('block_author_scans')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Block Author Enumeration', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Prevents bots from discovering usernames via ?author=N scans.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[hide_wp_version]" value="1" <?php checked($settings->get('hide_wp_version')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Hide WordPress Version', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Removes the WordPress version from the generator meta tag, scripts, and styles.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-card rfc-pro-section">
            <h3><?php esc_html_e('Advanced Security', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" name="rfc[change_login_url]" value="1" <?php checked($settings->get('change_login_url')); ?> disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Change Login URL', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Replace the default wp-login.php with a custom URL to prevent brute force attacks.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Custom Login Slug', 'rocketfuel-cache'); ?></label>
                <input type="text" value="<?php echo esc_attr($settings->get('login_url_slug')); ?>" class="rfc-input-wide" disabled placeholder="my-secret-login">
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" name="rfc[security_headers_enabled]" value="1" <?php checked($settings->get('security_headers_enabled')); ?> disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Security Headers', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Add X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, and Permissions-Policy headers.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Content Security Policy', 'rocketfuel-cache'); ?></label>
                <textarea rows="4" class="rfc-textarea" disabled placeholder="default-src 'self'; script-src 'self' 'unsafe-inline'"><?php echo esc_textarea($settings->get('header_csp')); ?></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('Define a Content Security Policy to prevent XSS and data injection attacks.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-submit-row">
            <button type="submit" name="rfc_save_settings" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Save Changes', 'rocketfuel-cache'); ?></button>
        </div>
    </form>
</div>
