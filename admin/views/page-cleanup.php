<?php defined('ABSPATH') || exit; ?>

<div class="rfc-settings-page">
    <form method="post" action="">
        <?php wp_nonce_field('rfc_save_settings', '_rfc_nonce'); ?>
        <input type="hidden" name="rfc_tab" value="cleanup">

        <div class="rfc-card">
            <h3><?php esc_html_e('WordPress Bloat Removal', 'rocketfuel-cache'); ?></h3>
            <p class="rfc-card-desc"><?php esc_html_e('Disable unnecessary WordPress features to reduce page weight and improve load times.', 'rocketfuel-cache'); ?></p>

            <div class="rfc-grid rfc-grid-2">

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[disable_emojis]" value="1" <?php checked($settings->get('disable_emojis')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Disable Emojis', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Remove WordPress emoji scripts and styles.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[disable_embeds]" value="1" <?php checked($settings->get('disable_embeds')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Disable Embeds', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Remove the oEmbed script that converts URLs to embeds.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[disable_dashicons]" value="1" <?php checked($settings->get('disable_dashicons')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Disable Dashicons', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Remove Dashicons CSS on the frontend for non-logged-in users.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[disable_xml_rpc]" value="1" <?php checked($settings->get('disable_xml_rpc')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Disable XML-RPC', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Disable the XML-RPC API. Keep enabled if using Jetpack or mobile apps.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[disable_rss]" value="1" <?php checked($settings->get('disable_rss')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Disable RSS Feeds', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Disable all RSS feeds. Only use if you do not need RSS.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[disable_self_pingbacks]" value="1" <?php checked($settings->get('disable_self_pingbacks')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Disable Self-Pingbacks', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Prevent your site from sending pingbacks to itself.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[disable_rest_api_public]" value="1" <?php checked($settings->get('disable_rest_api_public')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Restrict REST API', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Require authentication for REST API access.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[remove_wp_version]" value="1" <?php checked($settings->get('remove_wp_version')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Remove WP Version', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Remove the WordPress version meta tag from your HTML.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[remove_wlmanifest]" value="1" <?php checked($settings->get('remove_wlmanifest')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Remove WLManifest Link', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Remove the Windows Live Writer manifest link tag.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[remove_rsd]" value="1" <?php checked($settings->get('remove_rsd')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Remove RSD Link', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Remove the Really Simple Discovery link tag.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[remove_shortlink]" value="1" <?php checked($settings->get('remove_shortlink')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Remove Shortlinks', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Remove shortlink tags from the HTML head.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[remove_query_strings]" value="1" <?php checked($settings->get('remove_query_strings')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Remove Query Strings from Static Resources', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Remove version query strings (?ver=) from CSS and JS files.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[disable_google_maps]" value="1" <?php checked($settings->get('disable_google_maps')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Disable Google Maps', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Remove Google Maps API scripts. Enable only if you do not use Maps.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[disable_comments]" value="1" <?php checked($settings->get('disable_comments')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Disable Comments', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Completely disable the WordPress commenting system.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[disable_gravatar]" value="1" <?php checked($settings->get('disable_gravatar')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Disable Gravatars', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Replace Gravatar requests with a local placeholder.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[disable_block_library_css]" value="1" <?php checked($settings->get('disable_block_library_css')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Disable Block Library CSS', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Remove Gutenberg block library CSS on the frontend.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[disable_global_styles]" value="1" <?php checked($settings->get('disable_global_styles')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Disable Global Styles', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Remove the inline global styles and SVG filters added by WordPress.', 'rocketfuel-cache'); ?></p>
                </div>

                <div class="rfc-field">
                    <label class="rfc-toggle">
                        <input type="checkbox" name="rfc[disable_wc_bloat]" value="1" <?php checked($settings->get('disable_wc_bloat')); ?>>
                        <span class="rfc-toggle-slider"></span>
                    </label>
                    <span class="rfc-field-label"><?php esc_html_e('Disable WooCommerce Bloat', 'rocketfuel-cache'); ?></span>
                    <p class="rfc-field-desc"><?php esc_html_e('Remove WooCommerce scripts and styles on non-shop pages.', 'rocketfuel-cache'); ?></p>
                </div>
            </div>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('Post Revisions', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field rfc-field-inline">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[limit_revisions]" value="1" <?php checked($settings->get('limit_revisions')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Limit Post Revisions', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-revisions-count"><?php esc_html_e('Maximum Revisions', 'rocketfuel-cache'); ?></label>
                <input type="number" id="rfc-revisions-count" name="rfc[revisions_count]" value="<?php echo esc_attr($settings->get('revisions_count')); ?>" min="0" max="100" class="rfc-input-number">
                <p class="rfc-field-desc"><?php esc_html_e('Number of revisions to keep per post. Set to 0 to disable revisions entirely.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-submit-row">
            <button type="submit" name="rfc_save_settings" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Save Changes', 'rocketfuel-cache'); ?></button>
        </div>
    </form>
</div>
