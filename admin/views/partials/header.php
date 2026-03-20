<?php defined('ABSPATH') || exit; ?>

<div class="rfc-admin-wrap">

    <?php if ($settings->isSafeMode()) : ?>
        <div class="rfc-safe-mode-banner">
            <span class="dashicons dashicons-warning"></span>
            <?php esc_html_e('Safe Mode is active. All caching and optimization features are bypassed.', 'rocketfuel-cache'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=rocketfuel-cache-tools')); ?>"><?php esc_html_e('Disable', 'rocketfuel-cache'); ?></a>
        </div>
    <?php endif; ?>

    <div class="rfc-header">
        <div class="rfc-header-left">
            <h1 class="rfc-logo-title">
                <span class="dashicons dashicons-performance rfc-logo-icon"></span>
                <?php esc_html_e('RocketFuel Cache', 'rocketfuel-cache'); ?>
            </h1>
            <span class="rfc-version">v<?php echo esc_html(RFC_VERSION); ?></span>
        </div>
    </div>

    <nav class="rfc-tabs">
        <?php
        $all_tabs = [
            'dashboard'          => __('Dashboard', 'rocketfuel-cache'),
            'cache'              => __('Cache', 'rocketfuel-cache'),
            'file-optimization'  => __('File Optimization', 'rocketfuel-cache'),
            'image-optimization' => __('Image Optimization', 'rocketfuel-cache'),
            'media'              => __('Media', 'rocketfuel-cache'),
            'preloading'         => __('Preloading', 'rocketfuel-cache'),
            'script-manager'     => __('Script Manager', 'rocketfuel-cache'),
            'cleanup'            => __('WP Cleanup', 'rocketfuel-cache'),
            'database'           => __('Database', 'rocketfuel-cache'),
            'cdn'                => __('CDN', 'rocketfuel-cache'),
            'heartbeat'          => __('Heartbeat', 'rocketfuel-cache'),
            'local-hosting'      => __('Local Hosting', 'rocketfuel-cache'),
            'woocommerce'        => __('WooCommerce', 'rocketfuel-cache'),
            'security'           => __('Security', 'rocketfuel-cache'),
            'monitoring'         => __('Monitoring', 'rocketfuel-cache'),
            'reports'            => __('Reports', 'rocketfuel-cache'),
            'support'            => __('Support', 'rocketfuel-cache'),
            'tools'              => __('Tools', 'rocketfuel-cache'),
            'license'            => __('License', 'rocketfuel-cache'),
        ];

        foreach ($all_tabs as $slug => $label) :
            $url = $slug === 'dashboard'
                ? admin_url('admin.php?page=rocketfuel-cache')
                : admin_url('admin.php?page=rocketfuel-cache-' . $slug);
            $is_active = ($slug === $current_tab);
        ?>
            <a href="<?php echo esc_url($url); ?>" class="rfc-tab <?php echo $is_active ? 'rfc-tab-active' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="rfc-content">
