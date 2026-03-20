<?php defined('ABSPATH') || exit; ?>

<?php $is_pro = RFC_Engine::instance() && RFC_Engine::instance()->hasPro(); ?>

<div class="rfc-settings-page">

    <?php if (!$is_pro) : ?>
        <div class="rfc-card rfc-trial-banner">
            <div class="rfc-trial-info">
                <strong><?php esc_html_e('Pro Feature', 'rocketfuel-cache'); ?></strong>
                <span><?php esc_html_e('Local hosting of third-party scripts requires a Pro license. Host analytics, tag managers, and fonts locally for better performance and privacy compliance.', 'rocketfuel-cache'); ?></span>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=rocketfuel-cache-license')); ?>" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Upgrade to Pro', 'rocketfuel-cache'); ?></a>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('rfc_save_settings', '_rfc_nonce'); ?>
        <input type="hidden" name="rfc_tab" value="local-hosting">

        <div class="rfc-card rfc-pro-section">
            <h3><?php esc_html_e('Google Analytics', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Host Analytics Locally', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Download and serve Google Analytics scripts from your server. Improves page speed scores and works with ad blockers.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Measurement ID', 'rocketfuel-cache'); ?></label>
                <input type="text" class="rfc-input-wide" disabled placeholder="G-XXXXXXXXXX">
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Script Type', 'rocketfuel-cache'); ?></label>
                <select class="rfc-select" disabled>
                    <option>gtag.js</option>
                    <option>analytics.js</option>
                </select>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Position', 'rocketfuel-cache'); ?></label>
                <select class="rfc-select" disabled>
                    <option><?php esc_html_e('Header', 'rocketfuel-cache'); ?></option>
                    <option><?php esc_html_e('Footer', 'rocketfuel-cache'); ?></option>
                    <option><?php esc_html_e('After Interaction', 'rocketfuel-cache'); ?></option>
                </select>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Anonymize IP', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Minimal Analytics Mode', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Use a lightweight replacement script under 1KB instead of the full analytics library.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Update Schedule', 'rocketfuel-cache'); ?></label>
                <select class="rfc-select" disabled>
                    <option><?php esc_html_e('Daily', 'rocketfuel-cache'); ?></option>
                    <option><?php esc_html_e('Weekly', 'rocketfuel-cache'); ?></option>
                </select>
                <p class="rfc-field-desc"><?php esc_html_e('How often the local copy of the analytics script is refreshed from Google.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-card rfc-pro-section">
            <h3><?php esc_html_e('Cookie Consent', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Require Cookie Consent', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Only load analytics scripts after the visitor has given consent.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Consent Cookie Name', 'rocketfuel-cache'); ?></label>
                <input type="text" class="rfc-input-wide" disabled placeholder="cookieyes-consent">
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Consent Cookie Value', 'rocketfuel-cache'); ?></label>
                <input type="text" class="rfc-input-wide" disabled placeholder="yes">
            </div>

            <p class="rfc-field-desc rfc-pro-feature"><?php esc_html_e('Auto-detection works with CookieYes, Complianz, and CookieBot.', 'rocketfuel-cache'); ?></p>
        </div>

        <div class="rfc-card rfc-pro-section">
            <h3><?php esc_html_e('Google Tag Manager', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Host GTM Locally', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Download and serve the Google Tag Manager container script from your server.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Container ID', 'rocketfuel-cache'); ?></label>
                <input type="text" class="rfc-input-wide" disabled placeholder="GTM-XXXXXXX">
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Position', 'rocketfuel-cache'); ?></label>
                <select class="rfc-select" disabled>
                    <option><?php esc_html_e('Header', 'rocketfuel-cache'); ?></option>
                    <option><?php esc_html_e('Footer', 'rocketfuel-cache'); ?></option>
                </select>
            </div>
        </div>

        <div class="rfc-card rfc-pro-section">
            <h3><?php esc_html_e('Gravatars', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Cache Gravatars Locally', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Download and serve Gravatar images from your server to eliminate external requests.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Cache Duration', 'rocketfuel-cache'); ?></label>
                <select class="rfc-select" disabled>
                    <option><?php esc_html_e('Daily', 'rocketfuel-cache'); ?></option>
                    <option><?php esc_html_e('Weekly', 'rocketfuel-cache'); ?></option>
                    <option><?php esc_html_e('Monthly', 'rocketfuel-cache'); ?></option>
                </select>
            </div>
        </div>

        <div class="rfc-submit-row">
            <button type="submit" name="rfc_save_settings" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Save Changes', 'rocketfuel-cache'); ?></button>
        </div>
    </form>
</div>
