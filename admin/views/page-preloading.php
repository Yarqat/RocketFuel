<?php defined('ABSPATH') || exit; ?>

<?php $is_pro = RFC_Engine::instance() && RFC_Engine::instance()->hasPro(); ?>

<div class="rfc-settings-page">
    <form method="post" action="">
        <?php wp_nonce_field('rfc_save_settings', '_rfc_nonce'); ?>
        <input type="hidden" name="rfc_tab" value="preloading">

        <div class="rfc-card">
            <h3><?php esc_html_e('Cache Preloading', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" name="rfc[preload_enabled]" value="1" <?php checked($settings->get('preload_enabled')); ?>>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Enable Preloading', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Automatically crawl your site to build cache after it has been cleared.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-preload-sitemap"><?php esc_html_e('Sitemap URL', 'rocketfuel-cache'); ?></label>
                <input type="text" id="rfc-preload-sitemap" name="rfc[preload_sitemap_url]" value="<?php echo esc_attr($settings->get('preload_sitemap_url')); ?>" class="rfc-input-wide" placeholder="<?php echo esc_attr(home_url('/sitemap.xml')); ?>">
                <p class="rfc-field-desc"><?php esc_html_e('Leave empty to auto-detect your sitemap. Supports XML sitemaps from Yoast, Rank Math, and WordPress core.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-preload-rate"><?php esc_html_e('Preload Speed', 'rocketfuel-cache'); ?></label>
                <select id="rfc-preload-rate" name="rfc[preload_rate]" class="rfc-select">
                    <option value="slow" <?php selected($settings->get('preload_rate'), 'slow'); ?>><?php esc_html_e('Slow (1 request every 3 seconds)', 'rocketfuel-cache'); ?></option>
                    <option value="normal" <?php selected($settings->get('preload_rate'), 'normal'); ?>><?php esc_html_e('Normal (1 request per second)', 'rocketfuel-cache'); ?></option>
                    <option value="fast" <?php selected($settings->get('preload_rate'), 'fast'); ?>><?php esc_html_e('Fast (3 requests per second)', 'rocketfuel-cache'); ?></option>
                </select>
                <p class="rfc-field-desc"><?php esc_html_e('Choose how aggressively the preloader crawls your site. Use Slow on shared hosting.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <button type="button" id="rfc-preload-now" class="rfc-btn" data-action="rfc_preload"><?php esc_html_e('Preload Now', 'rocketfuel-cache'); ?></button>
            </div>

            <div id="rfc-preload-progress" class="rfc-progress-bar" style="display: none;">
                <div class="rfc-progress-fill"></div>
                <span class="rfc-progress-text"><?php esc_html_e('Preloading...', 'rocketfuel-cache'); ?></span>
            </div>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('Resource Hints', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-dns-prefetch"><?php esc_html_e('DNS Prefetch URLs', 'rocketfuel-cache'); ?></label>
                <textarea id="rfc-dns-prefetch" name="rfc[dns_prefetch_urls]" rows="4" class="rfc-textarea" placeholder="//fonts.googleapis.com&#10;//cdn.example.com"><?php echo esc_textarea($settings->get('dns_prefetch_urls')); ?></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('One domain per line. Resolves DNS for external domains before they are needed.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-preconnect"><?php esc_html_e('Preconnect URLs', 'rocketfuel-cache'); ?></label>
                <textarea id="rfc-preconnect" name="rfc[preconnect_urls]" rows="4" class="rfc-textarea" placeholder="https://fonts.gstatic.com&#10;https://cdn.example.com"><?php echo esc_textarea($settings->get('preconnect_urls')); ?></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('One domain per line. Establishes early connections including DNS, TCP, and TLS negotiation.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-preload-key"><?php esc_html_e('Preload Key Requests', 'rocketfuel-cache'); ?></label>
                <textarea id="rfc-preload-key" name="rfc[preload_fonts]" rows="5" class="rfc-textarea" placeholder="/wp-content/themes/theme/fonts/font.woff2|font&#10;/wp-content/themes/theme/style.css|style"><?php echo esc_textarea($settings->get('preload_fonts')); ?></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('One per line in format: url|type (types: font, style, script, image). These resources will be preloaded in the HTML head.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-card rfc-pro-section">
            <h3><?php esc_html_e('Instant Page', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>
            <p class="rfc-card-desc"><?php esc_html_e('Preloads pages just before a user clicks a link, making navigation feel instant.', 'rocketfuel-cache'); ?></p>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Enable Instant Page', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Uses just-in-time prefetching to load pages before the user clicks.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Trigger Delay (ms)', 'rocketfuel-cache'); ?></label>
                <input type="number" class="rfc-input-number" disabled value="65" min="0" max="500">
                <p class="rfc-field-desc"><?php esc_html_e('Milliseconds to wait after hover before prefetching. Lower values are more aggressive.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Max Prefetch Count', 'rocketfuel-cache'); ?></label>
                <input type="number" class="rfc-input-number" disabled value="10" min="1" max="50">
                <p class="rfc-field-desc"><?php esc_html_e('Maximum number of pages to prefetch per session to limit bandwidth usage.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-submit-row">
            <button type="submit" name="rfc_save_settings" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Save Changes', 'rocketfuel-cache'); ?></button>
        </div>
    </form>
</div>
