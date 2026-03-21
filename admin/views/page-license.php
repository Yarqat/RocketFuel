<?php defined('ABSPATH') || exit; ?>

<?php
$license_status = get_option('rfc_license_status', 'none');
$license_key = get_option('rfc_license_key', '');
$license_plan = get_option('rfc_license_plan', '');
$license_expiry = get_option('rfc_license_expiry', 0);
$trial_end = get_option('rfc_trial_ends_at', 0);
$trial_email = get_option('rfc_trial_email', '');
$trial_active = get_option('rfc_trial_active', false);
$has_pro = RFC_Engine::instance() && RFC_Engine::instance()->hasPro();

if ($trial_active && $license_status === 'none') {
    $license_status = 'trial';
}
if ($trial_active && $trial_end > 0 && $trial_end < time()) {
    $license_status = 'expired';
    update_option('rfc_trial_active', false);
}
?>

<div class="rfc-settings-page">

    <?php if ($license_status === 'trial' && !$has_pro) : ?>

        <div class="rfc-card" style="border-left: 3px solid var(--rfc-green, #00e676); padding: 20px;">
            <h3 style="color: var(--rfc-green, #00e676);">Trial Activated</h3>
            <?php
            $days_left = $trial_end > 0 ? max(0, ceil(($trial_end - time()) / 86400)) : 15;
            ?>
            <p>Your 15-day trial is active. <strong><?php echo esc_html($days_left); ?> days remaining.</strong></p>
            <p>Email: <?php echo esc_html($trial_email); ?></p>
            <p style="margin-top: 12px; padding: 10px; background: rgba(255,179,0,0.1); border-radius: 6px; color: var(--rfc-warning, #ffb300);">
                Pro features will be fully functional once the Pro module is downloaded from the server.
                The licensing server at manage.shahfahad.info is being configured.
            </p>
        </div>

    <?php elseif ($license_status === 'none') : ?>

        <div class="rfc-card rfc-license-activate">
            <h3><?php esc_html_e('Start Your Free 14-Day Trial', 'rocketfuel-cache'); ?></h3>
            <p><?php esc_html_e('Unlock all Pro features for 14 days. No credit card required.', 'rocketfuel-cache'); ?></p>
            <form id="rfc-trial-form">
                <?php wp_nonce_field('rfc_activate_trial', '_rfc_trial_nonce'); ?>
                <div class="rfc-field">
                    <label class="rfc-field-label" for="rfc-trial-email"><?php esc_html_e('Email Address', 'rocketfuel-cache'); ?></label>
                    <input type="email" id="rfc-trial-email" name="email" required class="rfc-input-wide" placeholder="you@example.com">
                </div>
                <button type="submit" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Activate Free Trial', 'rocketfuel-cache'); ?></button>
            </form>
        </div>

    <?php elseif ($license_status === 'trial') : ?>

        <?php
        $remaining = max(0, $trial_end - time());
        $days_left = ceil($remaining / DAY_IN_SECONDS);
        $progress = max(0, min(100, ($remaining / (14 * DAY_IN_SECONDS)) * 100));
        ?>

        <div class="rfc-card rfc-trial-active">
            <h3><?php esc_html_e('Free Trial Active', 'rocketfuel-cache'); ?></h3>
            <div class="rfc-trial-countdown">
                <div class="rfc-trial-bar-large">
                    <div class="rfc-trial-progress" style="width: <?php echo esc_attr($progress); ?>%"></div>
                </div>
                <p class="rfc-trial-days">
                    <?php printf(esc_html__('%d days remaining', 'rocketfuel-cache'), $days_left); ?>
                </p>
            </div>
            <p><?php printf(esc_html__('Registered email: %s', 'rocketfuel-cache'), esc_html($trial_email)); ?></p>
            <div class="rfc-license-actions">
                <button type="button" id="rfc-extend-trial-btn" class="rfc-btn"><?php esc_html_e('Extend Trial', 'rocketfuel-cache'); ?></button>
                <a href="#rfc-plans" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Upgrade to Pro', 'rocketfuel-cache'); ?></a>
            </div>
        </div>

    <?php elseif ($license_status === 'pro') : ?>

        <?php
        $masked_key = substr($license_key, 0, 4) . str_repeat('*', max(0, strlen($license_key) - 8)) . substr($license_key, -4);
        ?>

        <div class="rfc-card rfc-license-pro-card">
            <h3><?php esc_html_e('Pro License', 'rocketfuel-cache'); ?></h3>
            <table class="rfc-info-table">
                <tr>
                    <th><?php esc_html_e('License Key', 'rocketfuel-cache'); ?></th>
                    <td><code><?php echo esc_html($masked_key); ?></code></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Status', 'rocketfuel-cache'); ?></th>
                    <td><span class="rfc-status-badge rfc-status-on"><?php esc_html_e('Active', 'rocketfuel-cache'); ?></span></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Plan', 'rocketfuel-cache'); ?></th>
                    <td><?php echo esc_html(ucfirst($license_plan)); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Expires', 'rocketfuel-cache'); ?></th>
                    <td><?php echo $license_expiry > 0 ? esc_html(date_i18n(get_option('date_format'), $license_expiry)) : esc_html__('Lifetime', 'rocketfuel-cache'); ?></td>
                </tr>
            </table>
            <form id="rfc-deactivate-license-form">
                <?php wp_nonce_field('rfc_deactivate_license', '_rfc_deactivate_nonce'); ?>
                <button type="submit" class="rfc-btn rfc-btn-danger"><?php esc_html_e('Deactivate License', 'rocketfuel-cache'); ?></button>
            </form>
        </div>

    <?php elseif ($license_status === 'expired') : ?>

        <div class="rfc-card rfc-license-expired">
            <h3><?php esc_html_e('License Expired', 'rocketfuel-cache'); ?></h3>
            <p><?php esc_html_e('Your license or trial has expired. Renew to restore Pro features.', 'rocketfuel-cache'); ?></p>
            <a href="#rfc-plans" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Renew License', 'rocketfuel-cache'); ?></a>
        </div>

    <?php endif; ?>

    <div class="rfc-card" id="rfc-enter-license">
        <h3><?php esc_html_e('Enter License Key', 'rocketfuel-cache'); ?></h3>
        <form id="rfc-license-form">
            <?php wp_nonce_field('rfc_activate_license', '_rfc_license_nonce'); ?>
            <div class="rfc-field">
                <input type="text" id="rfc-license-key" name="license_key" class="rfc-input-wide" placeholder="XXXX-XXXX-XXXX-XXXX">
            </div>
            <button type="submit" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Activate License', 'rocketfuel-cache'); ?></button>
        </form>
    </div>

    <div class="rfc-card" id="rfc-plans">
        <h3><?php esc_html_e('Compare Plans', 'rocketfuel-cache'); ?></h3>

        <table class="rfc-plans-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Feature', 'rocketfuel-cache'); ?></th>
                    <th><?php esc_html_e('Free', 'rocketfuel-cache'); ?></th>
                    <th><?php esc_html_e('Pro', 'rocketfuel-cache'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e('Page Caching', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-yes">&#10003;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('CSS/JS Minification', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-yes">&#10003;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Lazy Loading', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-yes">&#10003;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('WordPress Cleanup', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-yes">&#10003;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Database Optimization', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-yes">&#10003;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Critical CSS Generation', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-no">&#10007;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Remove Unused CSS', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-no">&#10007;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Delay JavaScript', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-no">&#10007;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Image Optimization', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-no">&#10007;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Scheduled DB Cleanup', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-no">&#10007;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Security Headers & CSP', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-no">&#10007;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Custom Login URL', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-no">&#10007;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('White Label', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-no">&#10007;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Priority Support', 'rocketfuel-cache'); ?></td>
                    <td class="rfc-plan-no">&#10007;</td>
                    <td class="rfc-plan-yes">&#10003;</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="rfc-card">
        <h3><?php esc_html_e('Payment Options', 'rocketfuel-cache'); ?></h3>

        <div class="rfc-field">
            <label class="rfc-toggle rfc-toggle-disabled">
                <input type="checkbox" disabled>
                <span class="rfc-toggle-slider"></span>
            </label>
            <span class="rfc-field-label"><?php esc_html_e('Pay with Stripe', 'rocketfuel-cache'); ?></span>
            <p class="rfc-field-desc"><?php esc_html_e('Credit card payments via Stripe. Coming soon.', 'rocketfuel-cache'); ?></p>
        </div>

        <div class="rfc-field">
            <span class="rfc-field-label"><?php esc_html_e('Pay with Crypto', 'rocketfuel-cache'); ?></span>
            <p class="rfc-field-desc"><?php esc_html_e('Accept Bitcoin, Ethereum, and other cryptocurrencies. Contact support to arrange crypto payment.', 'rocketfuel-cache'); ?></p>
        </div>
    </div>

</div>
