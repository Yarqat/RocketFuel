<?php defined('ABSPATH') || exit; ?>

<?php
$is_pro = RFC_Engine::instance() && RFC_Engine::instance()->hasPro();
$woo_active = class_exists('WooCommerce');
?>

<div class="rfc-settings-page">

    <?php if (!$woo_active) : ?>
        <div class="rfc-card rfc-notice-inline rfc-notice-warning">
            <p><?php esc_html_e('WooCommerce is not detected. These settings will apply when WooCommerce is installed and activated.', 'rocketfuel-cache'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('rfc_save_settings', '_rfc_nonce'); ?>
        <input type="hidden" name="rfc_tab" value="woocommerce">

        <div class="rfc-card rfc-pro-section">
            <h3><?php esc_html_e('Cache Exclusions', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Auto-Exclude Dynamic Pages', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Automatically exclude Cart, Checkout, and My Account pages from caching.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-card rfc-pro-section">
            <h3><?php esc_html_e('Cart Fragments', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>
            <p class="rfc-card-desc"><?php esc_html_e('Cart fragments are AJAX requests that keep the mini-cart updated. They can significantly slow down page loads.', 'rocketfuel-cache'); ?></p>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Defer Cart Fragments', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Load cart fragments after the page has finished rendering.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Disable Cart Fragments', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Completely disable cart fragment AJAX requests. The mini-cart will not update dynamically.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Cart Fragments on Shop Pages Only', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Only load cart fragments on WooCommerce shop pages, not on blog posts or other pages.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-card rfc-pro-section">
            <h3><?php esc_html_e('Script Management', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Disable WC Scripts on Non-Shop Pages', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Remove WooCommerce CSS and JavaScript from pages that do not display products.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-field-label"><?php esc_html_e('Keep These Scripts', 'rocketfuel-cache'); ?></label>
                <textarea rows="4" class="rfc-textarea" disabled placeholder="wc-cart-fragments&#10;woocommerce-mini-cart"></textarea>
                <p class="rfc-field-desc"><?php esc_html_e('Script handles to keep even on non-shop pages. One per line. Useful for mini-cart widgets.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Disable WC Widgets on Non-Shop Pages', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Remove WooCommerce widget scripts and styles from non-shop pages.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <div class="rfc-card rfc-pro-section">
            <h3><?php esc_html_e('Cache Purging', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Purge on Stock Change', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Automatically clear cache for a product when its stock quantity changes.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Purge Shop Page', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Clear the main shop page cache when any product is updated.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field rfc-pro-feature">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Purge Category Pages', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Clear category and tag archive caches when products in those categories are updated.', 'rocketfuel-cache'); ?></p>
            </div>
        </div>

        <?php if ($woo_active) : ?>
            <div class="rfc-card">
                <h3><?php esc_html_e('WooCommerce Info', 'rocketfuel-cache'); ?></h3>
                <table class="rfc-info-table">
                    <tr>
                        <th><?php esc_html_e('WooCommerce Version', 'rocketfuel-cache'); ?></th>
                        <td><?php echo esc_html(WC()->version); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('HPOS Enabled', 'rocketfuel-cache'); ?></th>
                        <td>
                            <?php
                            if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && method_exists('Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled')) {
                                echo Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
                                    ? esc_html__('Yes', 'rocketfuel-cache')
                                    : esc_html__('No', 'rocketfuel-cache');
                            } else {
                                esc_html_e('Not available', 'rocketfuel-cache');
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        <?php endif; ?>

        <div class="rfc-submit-row">
            <button type="submit" name="rfc_save_settings" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Save Changes', 'rocketfuel-cache'); ?></button>
        </div>
    </form>
</div>
