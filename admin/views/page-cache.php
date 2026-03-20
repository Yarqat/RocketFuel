<?php defined('ABSPATH') || exit; ?>

<div class="rfc-settings-page">
    <form method="post" action="">
        <?php wp_nonce_field('rfc_save_settings', '_rfc_nonce'); ?>
        <input type="hidden" name="rfc_tab" value="cache">

        <div class="rfc-card">
            <h3><?php esc_html_e('Page Cache', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[page_cache_enabled]" value="1" <?php checked($settings->get('page_cache_enabled')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Enable Page Caching', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-cache-lifespan"><?php esc_html_e('Cache Lifespan (seconds)', 'rocketfuel-cache'); ?></label>
                <input type="number" id="rfc-cache-lifespan" name="rfc[cache_lifespan]" value="<?php echo esc_attr($settings->get('cache_lifespan')); ?>" min="0" step="1" class="rfc-input-number">
                <p class="rfc-field-desc"><?php esc_html_e('How long cached pages stay valid. Set to 0 for no expiration. Default: 36000 (10 hours).', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[mobile_cache]" value="1" <?php checked($settings->get('mobile_cache')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Separate Mobile Cache', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Create separate cache files for mobile devices. Enable if your theme uses different markup for mobile.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[logged_in_cache]" value="1" <?php checked($settings->get('logged_in_cache')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Cache for Logged-in Users', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Serve cached pages to logged-in users. Not recommended for sites with personalized content.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('Cache Behavior', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-strip-query"><?php esc_html_e('Strip Query Strings', 'rocketfuel-cache'); ?></label>
                <input type="text" id="rfc-strip-query" name="rfc[strip_query_strings]" value="<?php echo esc_attr($settings->get('strip_query_strings')); ?>" class="rfc-input-wide">
                <p class="rfc-field-desc"><?php esc_html_e('Comma-separated list of query parameters to strip from URLs before caching.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-cache-query"><?php esc_html_e('Cache Query Strings', 'rocketfuel-cache'); ?></label>
                <input type="text" id="rfc-cache-query" name="rfc[cache_query_strings]" value="<?php echo esc_attr($settings->get('cache_query_strings')); ?>" class="rfc-input-wide">
                <p class="rfc-field-desc"><?php esc_html_e('Comma-separated query parameters that should get their own cached version.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('Purge Settings', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[purge_on_post_update]" value="1" <?php checked($settings->get('purge_on_post_update')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Purge on Post Update', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[purge_homepage]" value="1" <?php checked($settings->get('purge_homepage')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Purge Homepage on Update', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[purge_archives]" value="1" <?php checked($settings->get('purge_archives')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Purge Archives on Update', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[purge_pagination]" value="1" <?php checked($settings->get('purge_pagination')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Purge Pagination', 'rocketfuel-cache'); ?></span>
            </div>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('Exclusions', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-never-cache-urls"><?php esc_html_e('Never Cache URLs', 'rocketfuel-cache'); ?></label>
                <textarea id="rfc-never-cache-urls" name="rfc[never_cache_urls]" rows="5" class="rfc-textarea" placeholder="/checkout&#10;/cart&#10;/my-account(.*)"><?php echo esc_textarea($settings->get('never_cache_urls')); ?></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('One per line. Supports regex patterns.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-never-cache-cookies"><?php esc_html_e('Never Cache Cookies', 'rocketfuel-cache'); ?></label>
                <textarea id="rfc-never-cache-cookies" name="rfc[never_cache_cookies]" rows="4" class="rfc-textarea"><?php echo esc_textarea($settings->get('never_cache_cookies')); ?></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('Comma-separated. Wildcards supported.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-never-cache-ua"><?php esc_html_e('Never Cache User Agents', 'rocketfuel-cache'); ?></label>
                <textarea id="rfc-never-cache-ua" name="rfc[never_cache_user_agents]" rows="4" class="rfc-textarea"><?php echo esc_textarea($settings->get('never_cache_user_agents')); ?></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('One per line. Matched against the User-Agent header.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-submit-row">
            <button type="submit" name="rfc_save_settings" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Save Changes', 'rocketfuel-cache'); ?></button>
        </div>
    </form>
</div>
