<?php defined('ABSPATH') || exit; ?>

<?php $is_pro = RFC_Engine::instance() && RFC_Engine::instance()->hasPro(); ?>

<div class="rfc-settings-page">
    <form method="post" action="">
        <?php wp_nonce_field('rfc_save_settings', '_rfc_nonce'); ?>
        <input type="hidden" name="rfc_tab" value="cdn">

        <div class="rfc-card">
            <h3><?php esc_html_e('CDN Configuration', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[cdn_enabled]" value="1" <?php checked($settings->get('cdn_enabled')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Enable CDN', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-cdn-url"><?php esc_html_e('CDN URL', 'rocketfuel-cache'); ?></label>
                <input type="text" id="rfc-cdn-url" name="rfc[cdn_url]" value="<?php echo esc_attr($settings->get('cdn_url')); ?>" class="rfc-input-wide" placeholder="https://cdn.example.com">
                <p class="rfc-field-desc"><?php esc_html_e('Enter your CDN URL without a trailing slash.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label"><?php esc_html_e('Included Directories', 'rocketfuel-cache'); ?></label>
                <?php $cdn_dirs = array_map('trim', explode(',', $settings->get('cdn_directories'))); ?>
                <label class="rfc-checkbox-label">
                    <input type="checkbox" name="rfc_cdn_dirs[]" value="wp-content" <?php checked(in_array('wp-content', $cdn_dirs)); ?>>
                    wp-content
                </label>
                <label class="rfc-checkbox-label">
                    <input type="checkbox" name="rfc_cdn_dirs[]" value="wp-includes" <?php checked(in_array('wp-includes', $cdn_dirs)); ?>>
                    wp-includes
                </label>
                <input type="hidden" name="rfc[cdn_directories]" id="rfc-cdn-directories" value="<?php echo esc_attr($settings->get('cdn_directories')); ?>">
                <p class="rfc-field-desc"><?php esc_html_e('Select which directories should be served through the CDN.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-cdn-excluded-ext"><?php esc_html_e('Excluded Extensions', 'rocketfuel-cache'); ?></label>
                <input type="text" id="rfc-cdn-excluded-ext" name="rfc[cdn_excluded_extensions]" value="<?php echo esc_attr($settings->get('cdn_excluded_extensions')); ?>" class="rfc-input-wide" placeholder=".php,.xml">
                <p class="rfc-field-desc"><?php esc_html_e('Comma-separated file extensions to exclude from CDN rewriting.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-cdn-excluded-urls"><?php esc_html_e('Excluded URLs', 'rocketfuel-cache'); ?></label>
                <textarea id="rfc-cdn-excluded-urls" name="rfc[cdn_excluded_urls]" rows="4" class="rfc-textarea" placeholder="/wp-content/uploads/private/&#10;/wp-content/plugins/my-plugin/"><?php echo esc_textarea($settings->get('cdn_excluded_urls')); ?></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('One URL path per line. These paths will not be rewritten to use the CDN.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-card rfc-pro-section">
            <h3><?php esc_html_e('CDN Integrations', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>
            <p class="rfc-card-desc"><?php esc_html_e('Connect directly to your CDN provider for automatic cache purging and management.', 'rocketfuel-cache'); ?></p>

            <div class="rfc-card rfc-pro-feature">
                <h4><?php esc_html_e('Cloudflare', 'rocketfuel-cache'); ?></h4>

                <div class="rfc-field">
                    <label class="rfc-toggle rfc-toggle-disabled">
                        <input type="checkbox" disabled>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Enable Cloudflare Integration', 'rocketfuel-cache'); ?></span>
                </div>

                <div class="rfc-field">
                    <label class="rfc-field-label"><?php esc_html_e('API Token', 'rocketfuel-cache'); ?></label>
                    <input type="text" class="rfc-input-wide" disabled placeholder="Enter your Cloudflare API Token">
                </div>

                <div class="rfc-field">
                    <label class="rfc-field-label"><?php esc_html_e('Zone ID', 'rocketfuel-cache'); ?></label>
                    <input type="text" class="rfc-input-wide" disabled placeholder="Enter your Cloudflare Zone ID">
                </div>
            </div>

            <div class="rfc-card rfc-pro-feature">
                <h4><?php esc_html_e('BunnyCDN', 'rocketfuel-cache'); ?></h4>

                <div class="rfc-field">
                    <label class="rfc-toggle rfc-toggle-disabled">
                        <input type="checkbox" disabled>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Enable BunnyCDN Integration', 'rocketfuel-cache'); ?></span>
                </div>

                <div class="rfc-field">
                    <label class="rfc-field-label"><?php esc_html_e('API Key', 'rocketfuel-cache'); ?></label>
                    <input type="text" class="rfc-input-wide" disabled placeholder="Enter your BunnyCDN API Key">
                </div>

                <div class="rfc-field">
                    <label class="rfc-field-label"><?php esc_html_e('Pull Zone ID', 'rocketfuel-cache'); ?></label>
                    <input type="text" class="rfc-input-wide" disabled placeholder="Enter your BunnyCDN Pull Zone ID">
                </div>
            </div>

            <div class="rfc-card rfc-pro-feature">
                <h4><?php esc_html_e('KeyCDN', 'rocketfuel-cache'); ?></h4>

                <div class="rfc-field">
                    <label class="rfc-toggle rfc-toggle-disabled">
                        <input type="checkbox" disabled>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Enable KeyCDN Integration', 'rocketfuel-cache'); ?></span>
                </div>

                <div class="rfc-field">
                    <label class="rfc-field-label"><?php esc_html_e('API Key', 'rocketfuel-cache'); ?></label>
                    <input type="text" class="rfc-input-wide" disabled placeholder="Enter your KeyCDN API Key">
                </div>
            </div>

            <div class="rfc-card rfc-pro-feature">
                <h4><?php esc_html_e('StackPath', 'rocketfuel-cache'); ?></h4>

                <div class="rfc-field">
                    <label class="rfc-toggle rfc-toggle-disabled">
                        <input type="checkbox" disabled>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Enable StackPath Integration', 'rocketfuel-cache'); ?></span>
                </div>

                <div class="rfc-field">
                    <label class="rfc-field-label"><?php esc_html_e('Client ID', 'rocketfuel-cache'); ?></label>
                    <input type="text" class="rfc-input-wide" disabled placeholder="Enter your StackPath Client ID">
                </div>

                <div class="rfc-field">
                    <label class="rfc-field-label"><?php esc_html_e('Client Secret', 'rocketfuel-cache'); ?></label>
                    <input type="text" class="rfc-input-wide" disabled placeholder="Enter your StackPath Client Secret">
                </div>
            </div>

            <div class="rfc-card rfc-pro-feature">
                <h4><?php esc_html_e('Varnish', 'rocketfuel-cache'); ?></h4>

                <div class="rfc-field">
                    <label class="rfc-toggle rfc-toggle-disabled">
                        <input type="checkbox" disabled>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Enable Varnish Integration', 'rocketfuel-cache'); ?></span>
                </div>

                <div class="rfc-field">
                    <label class="rfc-field-label"><?php esc_html_e('Varnish IP', 'rocketfuel-cache'); ?></label>
                    <input type="text" class="rfc-input-wide" disabled placeholder="127.0.0.1">
                </div>

                <div class="rfc-field">
                    <label class="rfc-field-label"><?php esc_html_e('Port', 'rocketfuel-cache'); ?></label>
                    <input type="text" class="rfc-input-wide" disabled placeholder="6081">
                </div>
            </div>

            <div class="rfc-card rfc-pro-feature">
                <h4><?php esc_html_e('Sucuri', 'rocketfuel-cache'); ?></h4>

                <div class="rfc-field">
                    <label class="rfc-toggle rfc-toggle-disabled">
                        <input type="checkbox" disabled>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Enable Sucuri Integration', 'rocketfuel-cache'); ?></span>
                </div>

                <div class="rfc-field">
                    <label class="rfc-field-label"><?php esc_html_e('API Key', 'rocketfuel-cache'); ?></label>
                    <input type="text" class="rfc-input-wide" disabled placeholder="Enter your Sucuri API Key">
                </div>

                <div class="rfc-field">
                    <label class="rfc-field-label"><?php esc_html_e('API Secret', 'rocketfuel-cache'); ?></label>
                    <input type="text" class="rfc-input-wide" disabled placeholder="Enter your Sucuri API Secret">
                </div>
            </div>
        </div>

        <div class="rfc-submit-row">
            <button type="submit" name="rfc_save_settings" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Save Changes', 'rocketfuel-cache'); ?></button>
        </div>
    </form>
</div>
