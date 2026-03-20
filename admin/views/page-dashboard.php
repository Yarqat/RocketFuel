<?php defined('ABSPATH') || exit; ?>

<div class="rfc-dashboard">

    <?php
    $license_status = get_option('rfc_license_status', 'none');
    $trial_end = get_option('rfc_trial_end', 0);
    $conflicts = get_option('rfc_conflicts', []);
    $events = get_option('rfc_recent_events', []);
    $cache_size = size_format(rfc_get_cache_size());
    $cached_pages = rfc_get_cached_page_count();
    $last_cleared = get_option('rfc_last_cleared', 0);
    ?>

    <?php if ($license_status === 'trial' && $trial_end > 0) : ?>
        <?php $days_left = max(0, ceil(($trial_end - time()) / DAY_IN_SECONDS)); ?>
        <div class="rfc-card rfc-trial-banner">
            <div class="rfc-trial-info">
                <strong><?php esc_html_e('Free Trial Active', 'rocketfuel-cache'); ?></strong>
                <span><?php printf(esc_html__('%d days remaining', 'rocketfuel-cache'), $days_left); ?></span>
            </div>
            <div class="rfc-trial-bar">
                <div class="rfc-trial-progress" style="width: <?php echo esc_attr(max(0, min(100, ($days_left / 14) * 100))); ?>%"></div>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=rocketfuel-cache-license')); ?>" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Upgrade to Pro', 'rocketfuel-cache'); ?></a>
        </div>
    <?php elseif ($license_status === 'pro') : ?>
        <div class="rfc-card rfc-license-banner rfc-license-pro">
            <strong><?php esc_html_e('Pro License Active', 'rocketfuel-cache'); ?></strong>
        </div>
    <?php elseif ($license_status === 'none') : ?>
        <div class="rfc-card rfc-trial-banner">
            <div class="rfc-trial-info">
                <strong><?php esc_html_e('Start Your Free 14-Day Trial', 'rocketfuel-cache'); ?></strong>
                <span><?php esc_html_e('Unlock all Pro features with no credit card required.', 'rocketfuel-cache'); ?></span>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=rocketfuel-cache-license')); ?>" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Activate Trial', 'rocketfuel-cache'); ?></a>
        </div>
    <?php endif; ?>

    <?php if (!empty($conflicts)) : ?>
        <div class="rfc-card rfc-conflict-warning">
            <h3><?php esc_html_e('Plugin Conflicts Detected', 'rocketfuel-cache'); ?></h3>
            <p><?php esc_html_e('The following plugins may conflict with RocketFuel Cache:', 'rocketfuel-cache'); ?></p>
            <ul>
                <?php foreach ($conflicts as $plugin_name) : ?>
                    <li><?php echo esc_html($plugin_name); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="rfc-card rfc-performance-card">
        <h3><?php esc_html_e('Performance Score', 'rocketfuel-cache'); ?></h3>
        <div class="rfc-score-placeholder">
            <div class="rfc-score-circle">
                <span class="rfc-score-value">--</span>
            </div>
            <p><?php esc_html_e('Run a performance test to see your score.', 'rocketfuel-cache'); ?></p>
        </div>
    </div>

    <div class="rfc-stats-row">
        <div class="rfc-card rfc-stat-box">
            <span class="rfc-stat-label"><?php esc_html_e('Cached Pages', 'rocketfuel-cache'); ?></span>
            <span class="rfc-stat-value"><?php echo esc_html($cached_pages); ?></span>
        </div>
        <div class="rfc-card rfc-stat-box">
            <span class="rfc-stat-label"><?php esc_html_e('Cache Size', 'rocketfuel-cache'); ?></span>
            <span class="rfc-stat-value"><?php echo esc_html($cache_size); ?></span>
        </div>
        <div class="rfc-card rfc-stat-box">
            <span class="rfc-stat-label"><?php esc_html_e('Last Cleared', 'rocketfuel-cache'); ?></span>
            <span class="rfc-stat-value">
                <?php
                if ($last_cleared > 0) {
                    echo esc_html(human_time_diff($last_cleared, current_time('timestamp'))) . ' ' . esc_html__('ago', 'rocketfuel-cache');
                } else {
                    esc_html_e('Never', 'rocketfuel-cache');
                }
                ?>
            </span>
        </div>
    </div>

    <div class="rfc-card rfc-quick-actions">
        <h3><?php esc_html_e('Quick Actions', 'rocketfuel-cache'); ?></h3>
        <div class="rfc-action-buttons">
            <form method="post" class="rfc-inline-form">
                <?php wp_nonce_field('rfc_quick_action', '_rfc_quick_nonce'); ?>
                <button type="button" class="rfc-btn rfc-btn-primary" data-action="rfc_purge_all">
                    <?php esc_html_e('Clear All Cache', 'rocketfuel-cache'); ?>
                </button>
                <button type="button" class="rfc-btn" data-action="rfc_preload">
                    <?php esc_html_e('Preload Cache', 'rocketfuel-cache'); ?>
                </button>
                <button type="button" class="rfc-btn" data-action="rfc_db_cleanup">
                    <?php esc_html_e('Run DB Cleanup', 'rocketfuel-cache'); ?>
                </button>
            </form>
        </div>
    </div>

    <div class="rfc-card rfc-recent-activity">
        <h3><?php esc_html_e('Recent Activity', 'rocketfuel-cache'); ?></h3>
        <?php if (empty($events)) : ?>
            <p class="rfc-muted"><?php esc_html_e('No recent activity.', 'rocketfuel-cache'); ?></p>
        <?php else : ?>
            <table class="rfc-activity-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Event', 'rocketfuel-cache'); ?></th>
                        <th><?php esc_html_e('Time', 'rocketfuel-cache'); ?></th>
                        <th><?php esc_html_e('User', 'rocketfuel-cache'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $display_events = array_slice($events, 0, 10);
                    foreach ($display_events as $event) :
                        $user = get_userdata($event['user']);
                        $username = $user ? $user->display_name : __('System', 'rocketfuel-cache');
                    ?>
                        <tr>
                            <td><?php echo esc_html(str_replace('_', ' ', ucfirst($event['type']))); ?></td>
                            <td><?php echo esc_html(human_time_diff($event['time'], current_time('timestamp'))) . ' ' . esc_html__('ago', 'rocketfuel-cache'); ?></td>
                            <td><?php echo esc_html($username); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="rfc-dashboard-status">
        <div class="rfc-card">
            <h3><?php esc_html_e('Feature Status', 'rocketfuel-cache'); ?></h3>
            <ul class="rfc-status-list">
                <li>
                    <span><?php esc_html_e('Page Cache', 'rocketfuel-cache'); ?></span>
                    <span class="rfc-status-badge <?php echo $settings->get('page_cache_enabled') ? 'rfc-status-on' : 'rfc-status-off'; ?>">
                        <?php echo $settings->get('page_cache_enabled') ? esc_html__('ON', 'rocketfuel-cache') : esc_html__('OFF', 'rocketfuel-cache'); ?>
                    </span>
                </li>
                <li>
                    <span><?php esc_html_e('CSS Minification', 'rocketfuel-cache'); ?></span>
                    <span class="rfc-status-badge <?php echo $settings->get('minify_css') ? 'rfc-status-on' : 'rfc-status-off'; ?>">
                        <?php echo $settings->get('minify_css') ? esc_html__('ON', 'rocketfuel-cache') : esc_html__('OFF', 'rocketfuel-cache'); ?>
                    </span>
                </li>
                <li>
                    <span><?php esc_html_e('JS Minification', 'rocketfuel-cache'); ?></span>
                    <span class="rfc-status-badge <?php echo $settings->get('minify_js') ? 'rfc-status-on' : 'rfc-status-off'; ?>">
                        <?php echo $settings->get('minify_js') ? esc_html__('ON', 'rocketfuel-cache') : esc_html__('OFF', 'rocketfuel-cache'); ?>
                    </span>
                </li>
                <li>
                    <span><?php esc_html_e('Lazy Load', 'rocketfuel-cache'); ?></span>
                    <span class="rfc-status-badge <?php echo $settings->get('lazy_load_images') ? 'rfc-status-on' : 'rfc-status-off'; ?>">
                        <?php echo $settings->get('lazy_load_images') ? esc_html__('ON', 'rocketfuel-cache') : esc_html__('OFF', 'rocketfuel-cache'); ?>
                    </span>
                </li>
                <li>
                    <span><?php esc_html_e('CDN', 'rocketfuel-cache'); ?></span>
                    <span class="rfc-status-badge <?php echo $settings->get('cdn_enabled') ? 'rfc-status-on' : 'rfc-status-off'; ?>">
                        <?php echo $settings->get('cdn_enabled') ? esc_html__('ON', 'rocketfuel-cache') : esc_html__('OFF', 'rocketfuel-cache'); ?>
                    </span>
                </li>
                <li>
                    <span><?php esc_html_e('Safe Mode', 'rocketfuel-cache'); ?></span>
                    <span class="rfc-status-badge <?php echo $settings->get('safe_mode') ? 'rfc-status-on' : 'rfc-status-off'; ?>">
                        <?php echo $settings->get('safe_mode') ? esc_html__('ON', 'rocketfuel-cache') : esc_html__('OFF', 'rocketfuel-cache'); ?>
                    </span>
                </li>
            </ul>
        </div>
    </div>

</div>
