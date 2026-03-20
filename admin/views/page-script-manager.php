<?php defined('ABSPATH') || exit; ?>

<?php $is_pro = RFC_Engine::instance() && RFC_Engine::instance()->hasPro(); ?>

<div class="rfc-settings-page">

    <?php if (!$is_pro) : ?>

        <div class="rfc-card" style="text-align: center; padding: 3rem 2rem;">
            <span class="dashicons dashicons-editor-code" style="font-size: 48px; width: 48px; height: 48px; color: #6366f1; margin-bottom: 1rem;"></span>
            <h3><?php esc_html_e('Script Manager', 'rocketfuel-cache'); ?></h3>
            <p><?php esc_html_e('Control exactly which CSS and JS files load on each page of your site.', 'rocketfuel-cache'); ?></p>

            <ul style="text-align: left; max-width: 480px; margin: 1.5rem auto; list-style: disc; padding-left: 1.5rem;">
                <li><?php esc_html_e('Disable Contact Form 7 scripts on pages without forms', 'rocketfuel-cache'); ?></li>
                <li><?php esc_html_e('Remove WooCommerce scripts from blog posts', 'rocketfuel-cache'); ?></li>
                <li><?php esc_html_e('See file sizes and source plugins at a glance', 'rocketfuel-cache'); ?></li>
                <li><?php esc_html_e('Set rules per page, per post type, by URL regex, or site-wide', 'rocketfuel-cache'); ?></li>
            </ul>

            <a href="<?php echo esc_url(admin_url('admin.php?page=rocketfuel-cache-license')); ?>" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Upgrade to Pro', 'rocketfuel-cache'); ?></a>
        </div>

    <?php else : ?>

        <div class="rfc-card">
            <h3><?php esc_html_e('Analyze Page', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field" style="display: flex; gap: 0.5rem; align-items: flex-start;">
                <input type="text" id="rfc-sm-url" class="rfc-input-wide" placeholder="<?php echo esc_attr(home_url('/')); ?>" value="<?php echo esc_attr(home_url('/')); ?>">
                <button type="button" id="rfc-sm-analyze" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Analyze', 'rocketfuel-cache'); ?></button>
            </div>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('CSS Files', 'rocketfuel-cache'); ?></h3>

            <div id="rfc-sm-css-placeholder" class="rfc-muted" style="padding: 2rem; text-align: center;">
                <p><?php esc_html_e('Enter a URL above and click Analyze to see all CSS files loaded on that page.', 'rocketfuel-cache'); ?></p>
            </div>

            <table id="rfc-sm-css-table" class="rfc-activity-table" style="display: none;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Handle', 'rocketfuel-cache'); ?></th>
                        <th><?php esc_html_e('Source', 'rocketfuel-cache'); ?></th>
                        <th><?php esc_html_e('Size', 'rocketfuel-cache'); ?></th>
                        <th><?php esc_html_e('Location', 'rocketfuel-cache'); ?></th>
                        <th><?php esc_html_e('Status', 'rocketfuel-cache'); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('JS Files', 'rocketfuel-cache'); ?></h3>

            <div id="rfc-sm-js-placeholder" class="rfc-muted" style="padding: 2rem; text-align: center;">
                <p><?php esc_html_e('Enter a URL above and click Analyze to see all JS files loaded on that page.', 'rocketfuel-cache'); ?></p>
            </div>

            <table id="rfc-sm-js-table" class="rfc-activity-table" style="display: none;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Handle', 'rocketfuel-cache'); ?></th>
                        <th><?php esc_html_e('Source', 'rocketfuel-cache'); ?></th>
                        <th><?php esc_html_e('Size', 'rocketfuel-cache'); ?></th>
                        <th><?php esc_html_e('Location', 'rocketfuel-cache'); ?></th>
                        <th><?php esc_html_e('Status', 'rocketfuel-cache'); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('Rule Settings', 'rocketfuel-cache'); ?></h3>

            <div class="rfc-field">
                <label class="rfc-field-label" for="rfc-sm-rule-type"><?php esc_html_e('Rule Type', 'rocketfuel-cache'); ?></label>
                <select id="rfc-sm-rule-type" class="rfc-select">
                    <option value="page"><?php esc_html_e('Per Page', 'rocketfuel-cache'); ?></option>
                    <option value="post_type"><?php esc_html_e('Per Post Type', 'rocketfuel-cache'); ?></option>
                    <option value="regex"><?php esc_html_e('Regex URL', 'rocketfuel-cache'); ?></option>
                    <option value="sitewide"><?php esc_html_e('Site-Wide', 'rocketfuel-cache'); ?></option>
                </select>
            </div>

            <div class="rfc-field">
                <label class="rfc-toggle">
                    <input type="checkbox" id="rfc-sm-test-mode">
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Test Mode', 'rocketfuel-cache'); ?></span>
            </div>

            <div id="rfc-sm-test-warning" class="rfc-notice-inline rfc-notice-warning" style="display: none;">
                <p><?php esc_html_e('Test Mode is active. Script rules are only applied for administrators. Regular visitors see all scripts as normal.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-submit-row">
            <button type="button" id="rfc-sm-save" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Save Rules', 'rocketfuel-cache'); ?></button>
        </div>

    <?php endif; ?>

</div>
