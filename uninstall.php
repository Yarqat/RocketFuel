<?php
defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('rfc_settings');
delete_option('rfc_version');
delete_option('rfc_activated_at');
delete_option('rfc_conflicts');
delete_option('rfc_foreign_dropin');
delete_option('rfc_wpconfig_readonly');
delete_option('rfc_preload_queue');
delete_option('rfc_preload_total');
delete_option('rfc_preload_started');
delete_option('rfc_script_rules');
delete_option('rfc_image_stats');
delete_option('rfc_cache_stats');
delete_option('rfc_daily_stats');

$prefix = '_rfc_';
global $wpdb;
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        $wpdb->esc_like($prefix) . '%'
    )
);

delete_transient('rfc_conflict_snoozed');

$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        '_transient_rfc_%',
        '_transient_timeout_rfc_%'
    )
);

delete_post_meta_by_key('_rfc_no_cache');
delete_post_meta_by_key('_rfc_cache_ttl');

$cache_dir = WP_CONTENT_DIR . '/cache/rocketfuel/';
if (is_dir($cache_dir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            @rmdir($item->getRealPath());
        } else {
            @unlink($item->getRealPath());
        }
    }
    @rmdir($cache_dir);
}

$dropin = WP_CONTENT_DIR . '/advanced-cache.php';
if (file_exists($dropin)) {
    $content = file_get_contents($dropin);
    if (strpos($content, 'RocketFuel') !== false) {
        @unlink($dropin);
    }
}

$config = ABSPATH . 'wp-config.php';
if (is_writable($config)) {
    $content = file_get_contents($config);
    $content = preg_replace(
        '/\n?define\s*\(\s*[\'"]WP_CACHE[\'"]\s*,\s*true\s*\)\s*;\s*\n?/',
        "\n",
        $content
    );
    @file_put_contents($config, $content);
}

if (function_exists('insert_with_markers')) {
    $htaccess = ABSPATH . '.htaccess';
    if (file_exists($htaccess) && is_writable($htaccess)) {
        insert_with_markers($htaccess, 'RocketFuel Cache', []);
    }
}

$hooks = ['rfc_preload_event', 'rfc_db_cleanup_event', 'rfc_analytics_update_event', 'rfc_heartbeat_check'];
foreach ($hooks as $hook) {
    $ts = wp_next_scheduled($hook);
    if ($ts) {
        wp_unschedule_event($ts, $hook);
    }
}
