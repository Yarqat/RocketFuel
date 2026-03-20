<?php
defined('ABSPATH') || exit;

$rfc_cache_dir = '{{RFC_CACHE_DIR}}';
$rfc_request   = $_SERVER['REQUEST_METHOD'] ?? '';
$rfc_uri       = $_SERVER['REQUEST_URI'] ?? '';
$rfc_host      = $_SERVER['HTTP_HOST'] ?? '';
$rfc_scheme    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

if ($rfc_request !== 'GET') return;
if (!empty($_POST)) return;
if (defined('DOING_AJAX') || defined('DOING_CRON') || defined('WP_CLI')) return;

foreach ($_COOKIE as $rfc_ck => $rfc_cv) {
    if (strpos($rfc_ck, 'wordpress_logged_in_') === 0) return;
    if (strpos($rfc_ck, 'woocommerce_items_in_cart') === 0) return;
    if (strpos($rfc_ck, 'wp_woocommerce_session_') === 0) return;
}

$rfc_strip = ['utm_source','utm_medium','utm_campaign','utm_term','utm_content','fbclid','gclid','mc_cid','mc_eid'];
$rfc_path  = $rfc_uri;
if (($rfc_qpos = strpos($rfc_path, '?')) !== false) {
    $rfc_qs = substr($rfc_path, $rfc_qpos + 1);
    $rfc_path = substr($rfc_path, 0, $rfc_qpos);
    parse_str($rfc_qs, $rfc_params);
    foreach ($rfc_strip as $rfc_sk) unset($rfc_params[$rfc_sk]);
    if (!empty($rfc_params)) return;
}

$rfc_path = trim($rfc_path, '/');
if ($rfc_path === '') $rfc_path = 'index';

$rfc_segments = explode('/', $rfc_path);
$rfc_safe     = [];
foreach ($rfc_segments as $rfc_seg) {
    $rfc_seg = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $rfc_seg);
    if ($rfc_seg !== '') $rfc_safe[] = $rfc_seg;
}

$rfc_file_base = $rfc_cache_dir . preg_replace('/[^a-zA-Z0-9.\-]/', '', $rfc_host) . '/' . implode('/', $rfc_safe) . '/';
$rfc_file      = $rfc_file_base . "index-{$rfc_scheme}.html";
$rfc_file_gz   = $rfc_file . '.gz';

if (!file_exists($rfc_file)) return;

$rfc_settings = @get_option('rfc_settings');
$rfc_ttl      = is_array($rfc_settings) && isset($rfc_settings['cache_lifespan']) ? (int)$rfc_settings['cache_lifespan'] : 36000;

if ($rfc_ttl > 0 && (time() - filemtime($rfc_file)) > $rfc_ttl) {
    @unlink($rfc_file);
    if (file_exists($rfc_file_gz)) @unlink($rfc_file_gz);
    return;
}

header('X-RocketFuel-Cache: HIT');
header('X-RocketFuel-Source: dropin');
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($rfc_file)) . ' GMT');

$rfc_accept = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
if (strpos($rfc_accept, 'gzip') !== false && file_exists($rfc_file_gz)) {
    header('Content-Encoding: gzip');
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Length: ' . filesize($rfc_file_gz));
    readfile($rfc_file_gz);
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
header('Content-Length: ' . filesize($rfc_file));
readfile($rfc_file);
exit;
