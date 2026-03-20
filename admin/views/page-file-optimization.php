<?php defined('ABSPATH') || exit; ?>

<div class="rfc-settings-page">
    <form method="post" action="">
        <?php wp_nonce_field('rfc_save_settings', '_rfc_nonce'); ?>
        <input type="hidden" name="rfc_tab" value="file-optimization">

        <div class="rfc-card">
            <h3><?php esc_html_e('HTML', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[minify_html]" value="1" <?php checked($settings->get('minify_html')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Minify HTML', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[remove_html_comments]" value="1" <?php checked($settings->get('remove_html_comments')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Remove HTML Comments', 'rocketfuel-cache'); ?></span>
            </div>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('CSS Files', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[minify_css]" value="1" <?php checked($settings->get('minify_css')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Minify CSS', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[combine_css]" value="1" <?php checked($settings->get('combine_css')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Combine CSS Files', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Generate Critical CSS', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></span>
                <p class="rfc-field-desc"><?php esc_html_e('Automatically extract and inline above-the-fold CSS for faster rendering.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Remove Unused CSS', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></span>
                <p class="rfc-field-desc"><?php esc_html_e('Strip CSS rules not used on each page for smaller stylesheets.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Inline Small CSS', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></span>
                <p class="rfc-field-desc"><?php esc_html_e('Inline CSS files smaller than 6KB to eliminate render-blocking requests.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-css-exclusions"><?php esc_html_e('CSS Exclusions', 'rocketfuel-cache'); ?></label>
                <textarea id="rfc-css-exclusions" name="rfc[css_exclusions]" rows="4" class="rfc-textarea" placeholder="handle-name&#10;/path/to/file.css"><?php echo esc_textarea($settings->get('css_exclusions')); ?></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('One per line. Handle names or file paths to exclude from minification/combining.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('JavaScript Files', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[minify_js]" value="1" <?php checked($settings->get('minify_js')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Minify JavaScript', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[combine_js]" value="1" <?php checked($settings->get('combine_js')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Combine JavaScript Files', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[defer_js]" value="1" <?php checked($settings->get('defer_js')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Defer JavaScript', 'rocketfuel-cache'); ?></span>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-defer-js-exclusions"><?php esc_html_e('Defer Exclusions', 'rocketfuel-cache'); ?></label>
                <textarea id="rfc-defer-js-exclusions" name="rfc[defer_js_exclusions]" rows="3" class="rfc-textarea" placeholder="jquery-core"><?php echo esc_textarea($settings->get('defer_js_exclusions')); ?></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('Scripts that should not be deferred. One handle per line.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[remove_jquery_migrate]" value="1" <?php checked($settings->get('remove_jquery_migrate')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Remove jQuery Migrate', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Remove the jQuery Migrate script on the frontend. Only disable if your theme/plugins do not need it.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Delay JavaScript Execution', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></span>
                <p class="rfc-field-desc"><?php esc_html_e('Delay loading of JS until user interaction for better Core Web Vitals.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Inline Small JS', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></span>
                <p class="rfc-field-desc"><?php esc_html_e('Inline JavaScript files smaller than 6KB to reduce HTTP requests.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-js-exclusions"><?php esc_html_e('JS Exclusions', 'rocketfuel-cache'); ?></label>
                <textarea id="rfc-js-exclusions" name="rfc[js_exclusions]" rows="4" class="rfc-textarea" placeholder="handle-name&#10;/path/to/script.js"><?php echo esc_textarea($settings->get('js_exclusions')); ?></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('One per line. Handle names or file paths to exclude from minification/combining.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-submit-row">
            <button type="submit" name="rfc_save_settings" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Save Changes', 'rocketfuel-cache'); ?></button>
        </div>
    </form>
</div>
