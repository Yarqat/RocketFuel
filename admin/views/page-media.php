<?php defined('ABSPATH') || exit; ?>

<?php $is_pro = RFC_Engine::instance() && RFC_Engine::instance()->hasPro(); ?>

<div class="rfc-settings-page">
    <form method="post" action="">
        <?php wp_nonce_field('rfc_save_settings', '_rfc_nonce'); ?>
        <input type="hidden" name="rfc_tab" value="media">

        <div class="rfc-card">
            <h3><?php esc_html_e('Lazy Loading', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[lazy_load_images]" value="1" <?php checked($settings->get('lazy_load_images')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Lazy Load Images', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-lazy-method"><?php esc_html_e('Lazy Load Method', 'rocketfuel-cache'); ?></label>
                <select id="rfc-lazy-method" name="rfc[lazy_load_method]" class="rfc-select">
                    <option value="native" <?php selected($settings->get('lazy_load_method'), 'native'); ?>><?php esc_html_e('Native Only (loading="lazy")', 'rocketfuel-cache'); ?></option>
                    <option value="native_js" <?php selected($settings->get('lazy_load_method'), 'native_js'); ?>><?php esc_html_e('Native + JS Fallback', 'rocketfuel-cache'); ?></option>
                    <option value="js" <?php selected($settings->get('lazy_load_method'), 'js'); ?>><?php esc_html_e('JavaScript Only', 'rocketfuel-cache'); ?></option>
                </select>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-lazy-skip"><?php esc_html_e('Skip First N Images', 'rocketfuel-cache'); ?></label>
                <input type="number" id="rfc-lazy-skip" name="rfc[lazy_load_exclude_count]" value="<?php echo esc_attr($settings->get('lazy_load_exclude_count')); ?>" min="0" max="20" class="rfc-input-number">
                <p class="rfc-field-desc"><?php esc_html_e('Number of images to skip from the top of the page. These load immediately for better LCP.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-lazy-placeholder"><?php esc_html_e('Placeholder Style', 'rocketfuel-cache'); ?></label>
                <select id="rfc-lazy-placeholder" name="rfc[lazy_load_placeholder]" class="rfc-select">
                    <option value="transparent" <?php selected($settings->get('lazy_load_placeholder'), 'transparent'); ?>><?php esc_html_e('Transparent', 'rocketfuel-cache'); ?></option>
                    <option value="gray" <?php selected($settings->get('lazy_load_placeholder'), 'gray'); ?>><?php esc_html_e('Gray', 'rocketfuel-cache'); ?></option>
                </select>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[lazy_load_iframes]" value="1" <?php checked($settings->get('lazy_load_iframes')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Lazy Load iframes', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[lazy_load_videos]" value="1" <?php checked($settings->get('lazy_load_videos')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Lazy Load Videos', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-lazy-exclusions"><?php esc_html_e('Exclusions', 'rocketfuel-cache'); ?></label>
                <textarea id="rfc-lazy-exclusions" name="rfc[lazy_load_exclusions]" rows="4" class="rfc-textarea" placeholder="no-lazy&#10;skip-lazy&#10;.hero-image"><?php echo esc_textarea($settings->get('lazy_load_exclusions')); ?></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('CSS classes or IDs to exclude from lazy loading. One per line.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('Video Optimization', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[youtube_thumbnail_swap]" value="1" <?php checked($settings->get('youtube_thumbnail_swap')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('YouTube Thumbnail Swap', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Replace YouTube embeds with a lightweight thumbnail. The player loads only when clicked, saving significant page weight.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[vimeo_thumbnail_swap]" value="1" <?php checked($settings->get('vimeo_thumbnail_swap')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Vimeo Thumbnail Swap', 'rocketfuel-cache'); ?></span>
            </div>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('Image Attributes', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[add_missing_dimensions]" value="1" <?php checked($settings->get('add_missing_dimensions')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Add Missing Dimensions', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Automatically add width and height attributes to images that are missing them. Reduces Cumulative Layout Shift (CLS).', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('Font Optimization', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[local_google_fonts]" value="1" <?php checked($settings->get('local_google_fonts')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Host Google Fonts Locally', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Download and serve Google Fonts from your server for better performance and GDPR compliance.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-font-display"><?php esc_html_e('Font Display', 'rocketfuel-cache'); ?></label>
                <select id="rfc-font-display" name="rfc[font_display]" class="rfc-select">
                    <option value="swap" <?php selected($settings->get('font_display'), 'swap'); ?>><?php esc_html_e('swap', 'rocketfuel-cache'); ?></option>
                    <option value="block" <?php selected($settings->get('font_display'), 'block'); ?>><?php esc_html_e('block', 'rocketfuel-cache'); ?></option>
                    <option value="fallback" <?php selected($settings->get('font_display'), 'fallback'); ?>><?php esc_html_e('fallback', 'rocketfuel-cache'); ?></option>
                    <option value="optional" <?php selected($settings->get('font_display'), 'optional'); ?>><?php esc_html_e('optional', 'rocketfuel-cache'); ?></option>
                </select>
                <p class="rfc-field-desc"><?php esc_html_e('Controls how fonts are displayed while loading. "swap" is recommended for most sites.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[disable_google_fonts]" value="1" <?php checked($settings->get('disable_google_fonts')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Disable Google Fonts Entirely', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Remove all Google Fonts requests. Your theme will fall back to system fonts.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-preload-fonts"><?php esc_html_e('Preload Fonts', 'rocketfuel-cache'); ?></label>
                <textarea id="rfc-preload-fonts" name="rfc[preload_fonts]" rows="4" class="rfc-textarea" placeholder="/wp-content/themes/theme/fonts/font.woff2"><?php echo esc_textarea($settings->get('preload_fonts')); ?></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('WOFF2 font URLs to preload. One URL per line. Only preload fonts used above the fold.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-card rfc-pro-section">
            <h3><?php esc_html_e('Advanced Media', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Lazy Load CSS Backgrounds', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Defer loading of CSS background images until they enter the viewport.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('LQIP Blurred Placeholders', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Show a low-quality blurred version of the image while the full version loads.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Adaptive Images', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Serve appropriately sized images based on the visitor device screen size.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Disable Google Maps', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Remove Google Maps API scripts from all pages.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Local Gravatars', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Cache Gravatar images locally to reduce external requests.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Gravatar Cache Duration', 'rocketfuel-cache'); ?></label>
                <select class="rfc-select" disabled>
                    <option><?php esc_html_e('Daily', 'rocketfuel-cache'); ?></option>
                    <option selected><?php esc_html_e('Weekly', 'rocketfuel-cache'); ?></option>
                    <option><?php esc_html_e('Monthly', 'rocketfuel-cache'); ?></option>
                </select>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Disable Right-Click on Images', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Prevent visitors from right-clicking to save your images.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Font Subsetting', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Reduce font file sizes by removing unused character sets and glyphs.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-submit-row">
            <button type="submit" name="rfc_save_settings" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Save Changes', 'rocketfuel-cache'); ?></button>
        </div>
    </form>
</div>
