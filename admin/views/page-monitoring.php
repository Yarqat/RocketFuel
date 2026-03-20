<?php defined('ABSPATH') || exit; ?>

<?php
$is_pro = RFC_Engine::instance() && RFC_Engine::instance()->hasPro();

$cache_dir = RFC_CACHE_DIR;
$cached_files = 0;
$cache_size = 0;
$cache_active = $settings->get('page_cache_enabled');
$last_cleared = get_option('rfc_last_cleared', 0);

if (is_dir($cache_dir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'html') {
            $cached_files++;
        }
        if ($file->isFile()) {
            $cache_size += $file->getSize();
        }
    }
}
?>

<div class="rfc-settings-page">

    <div class="rfc-card">
        <h3><?php esc_html_e('Cache Status', 'rocketfuel-cache'); ?></h3>

        <div class="rfc-stats-row">
            <div class="rfc-card rfc-stat-box">
                <span class="rfc-stat-label"><?php esc_html_e('Page Cache', 'rocketfuel-cache'); ?></span>
                <span class="rfc-stat-value">
                    <span class="rfc-status-badge <?php echo $cache_active ? 'rfc-status-on' : 'rfc-status-off'; ?>">
                        <?php echo $cache_active ? esc_html__('ON', 'rocketfuel-cache') : esc_html__('OFF', 'rocketfuel-cache'); ?>
                    </span>
                </span>
            </div>
            <div class="rfc-card rfc-stat-box">
                <span class="rfc-stat-label"><?php esc_html_e('Cached Pages', 'rocketfuel-cache'); ?></span>
                <span class="rfc-stat-value"><?php echo esc_html(number_format_i18n($cached_files)); ?></span>
            </div>
            <div class="rfc-card rfc-stat-box">
                <span class="rfc-stat-label"><?php esc_html_e('Cache Size', 'rocketfuel-cache'); ?></span>
                <span class="rfc-stat-value"><?php echo esc_html(size_format($cache_size)); ?></span>
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
    </div>

    <div class="rfc-card rfc-pro-section">
        <h3><?php esc_html_e('Cache Analytics', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

        <div class="rfc-pro-feature">
            <div id="rfc-hit-chart" style="width: 100%; height: 300px; background: #f8f9fa; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                <p class="rfc-muted"><?php esc_html_e('Cache hit/miss chart available in Pro', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-stats-row" style="margin-top: 1rem;">
                <div class="rfc-card rfc-stat-box">
                    <span class="rfc-stat-label"><?php esc_html_e('Hit Rate', 'rocketfuel-cache'); ?></span>
                    <span class="rfc-stat-value">--%</span>
                </div>
            </div>

            <p class="rfc-field-desc"><?php esc_html_e('Cache analytics tracks hit/miss ratio, bandwidth saved, and performance over time.', 'rocketfuel-cache'); ?></p>
        </div>
    </div>

    <div class="rfc-card rfc-pro-section">
        <h3><?php esc_html_e('PageSpeed Insights', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

        <div class="rfc-pro-feature">
            <div class="rfc-stats-row">
                <div class="rfc-card rfc-stat-box">
                    <span class="rfc-stat-label"><?php esc_html_e('Mobile Score', 'rocketfuel-cache'); ?></span>
                    <span class="rfc-stat-value">--</span>
                </div>
                <div class="rfc-card rfc-stat-box">
                    <span class="rfc-stat-label"><?php esc_html_e('Desktop Score', 'rocketfuel-cache'); ?></span>
                    <span class="rfc-stat-value">--</span>
                </div>
            </div>

            <div class="rfc-field" style="margin-top: 1rem;">
                <button type="button" class="rfc-btn" disabled><?php esc_html_e('Run Test Now', 'rocketfuel-cache'); ?></button>
            </div>

            <p class="rfc-field-desc"><?php esc_html_e('Tracks your Google PageSpeed score over time so you can measure the impact of your optimizations.', 'rocketfuel-cache'); ?></p>
        </div>
    </div>

    <div class="rfc-card rfc-pro-section">
        <h3><?php esc_html_e('Image Optimization Stats', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

        <div class="rfc-pro-feature">
            <div class="rfc-stats-row">
                <div class="rfc-card rfc-stat-box">
                    <span class="rfc-stat-label"><?php esc_html_e('Images Optimized', 'rocketfuel-cache'); ?></span>
                    <span class="rfc-stat-value">--</span>
                </div>
                <div class="rfc-card rfc-stat-box">
                    <span class="rfc-stat-label"><?php esc_html_e('Storage Saved', 'rocketfuel-cache'); ?></span>
                    <span class="rfc-stat-value">--</span>
                </div>
                <div class="rfc-card rfc-stat-box">
                    <span class="rfc-stat-label"><?php esc_html_e('Avg. Compression', 'rocketfuel-cache'); ?></span>
                    <span class="rfc-stat-value">--%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="rfc-card rfc-pro-section">
        <h3><?php esc_html_e('Email Reports', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

        <div class="rfc-pro-feature">
            <div class="rfc-field">
                <label class="rfc-toggle rfc-toggle-disabled">
                    <input type="checkbox" disabled>
                    <span class="rfc-toggle-slider"></span>
                </label>
                <span class="rfc-field-label"><?php esc_html_e('Enable Weekly Report', 'rocketfuel-cache'); ?></span>
                <p class="rfc-field-desc"><?php esc_html_e('Receive a weekly email summary of cache performance, optimization stats, and recommendations.', 'rocketfuel-cache'); ?></p>
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label"><?php esc_html_e('Email Address', 'rocketfuel-cache'); ?></label>
                <input type="email" class="rfc-input-wide" disabled value="<?php echo esc_attr(get_option('admin_email')); ?>">
            </div>

            <div class="rfc-field">
                <label class="rfc-field-label"><?php esc_html_e('Last Report Sent', 'rocketfuel-cache'); ?></label>
                <input type="text" class="rfc-input-wide" disabled value="<?php esc_attr_e('Never', 'rocketfuel-cache'); ?>" readonly>
            </div>
        </div>
    </div>

</div>
