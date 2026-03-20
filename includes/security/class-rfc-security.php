<?php
defined('ABSPATH') || exit;

final class RFC_Security {

    private $settings;
    private $proActive = false;

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        if ($this->settings->isSafeMode()) {
            return;
        }

        $this->proActive = RFC_Engine::instance() && RFC_Engine::instance()->hasPro();

        $this->earlyGuards();
        add_action('init', [$this, 'applyRules'], 0);
        add_action('template_redirect', [$this, 'templateGuards'], 0);

        if ($this->proActive) {
            add_action('send_headers', [$this, 'injectSecurityHeaders'], 1);
            add_action('init', [$this, 'handleLoginUrlRewrite'], -1);
        }
    }

    private function earlyGuards() {
        if ($this->settings->get('disable_file_editor', false)) {
            if (!defined('DISALLOW_FILE_EDIT')) {
                define('DISALLOW_FILE_EDIT', true);
            }
        }
    }

    public function applyRules() {
        if ($this->settings->get('hide_wp_version', true)) {
            remove_action('wp_head', 'wp_generator');
            add_filter('the_generator', '__return_empty_string');
            add_filter('style_loader_src', [$this, 'scrubVersion'], 9999);
            add_filter('script_loader_src', [$this, 'scrubVersion'], 9999);
        }

        if ($this->settings->get('block_author_scans', true)) {
            add_action('template_redirect', [$this, 'blockAuthorEnumeration'], 1);
        }

        if ($this->settings->get('disable_directory_browsing', true)) {
            $this->ensureDirectoryProtection();
        }

        if ($this->proActive) {
            $this->proRules();
        }
    }

    public function templateGuards() {
        if ($this->settings->get('block_author_scans', true) && !is_admin()) {
            if (isset($_GET['author']) && is_numeric($_GET['author'])) {
                wp_redirect(home_url(), 301);
                exit;
            }
        }
    }

    public function blockAuthorEnumeration() {
        if (is_admin()) {
            return;
        }

        if (is_author()) {
            $requested = get_query_var('author');
            if (!empty($requested) && is_numeric($requested)) {
                wp_redirect(home_url(), 301);
                exit;
            }
        }
    }

    public function scrubVersion($src) {
        if (empty($src) || strpos($src, '?') === false) {
            return $src;
        }

        $parts = explode('?', $src, 2);
        parse_str($parts[1], $params);
        unset($params['ver']);

        if (empty($params)) {
            return $parts[0];
        }

        return $parts[0] . '?' . http_build_query($params);
    }

    private function ensureDirectoryProtection() {
        $htaccess = ABSPATH . '.htaccess';

        if (!file_exists($htaccess) || !is_writable($htaccess)) {
            return;
        }

        $contents = file_get_contents($htaccess);
        if ($contents === false) {
            return;
        }

        if (strpos($contents, 'Options -Indexes') !== false) {
            return;
        }

        $marker = '# RocketFuel Directory Protection';
        if (strpos($contents, $marker) !== false) {
            return;
        }

        $rule = "\n{$marker}\nOptions -Indexes\n# End RocketFuel Directory Protection\n";
        file_put_contents($htaccess, $contents . $rule);
    }

    private function proRules() {
        if ($this->settings->get('remove_generator_tags', false)) {
            add_filter('the_generator', '__return_empty_string');
            add_filter('get_the_generator_html', '__return_empty_string');
            add_filter('get_the_generator_xhtml', '__return_empty_string');
            add_filter('get_the_generator_atom', '__return_empty_string');
            add_filter('get_the_generator_rss2', '__return_empty_string');
            add_filter('get_the_generator_rdf', '__return_empty_string');
            add_filter('get_the_generator_comment', '__return_empty_string');
            add_filter('get_the_generator_export', '__return_empty_string');

            add_action('wp_head', function () {
                ob_start(function ($output) {
                    return preg_replace('/<meta[^>]*name=["\']generator["\'][^>]*>/i', '', $output);
                });
            }, 0);

            add_action('wp_footer', function () {
                if (ob_get_level() > 0) {
                    ob_end_flush();
                }
            }, 9999);
        }
    }

    public function injectSecurityHeaders() {
        if (!$this->settings->get('security_headers_enabled', false)) {
            return;
        }

        if (headers_sent()) {
            return;
        }

        if ($this->settings->get('header_x_content_type', true)) {
            header('X-Content-Type-Options: nosniff');
        }

        $xframe = $this->settings->get('header_x_frame_options', 'SAMEORIGIN');
        if (!empty($xframe) && $xframe !== 'disabled') {
            header('X-Frame-Options: ' . $xframe);
        }

        if ($this->settings->get('header_x_xss_protection', true)) {
            header('X-XSS-Protection: 1; mode=block');
        }

        $referrer = $this->settings->get('header_referrer_policy', 'strict-origin-when-cross-origin');
        if (!empty($referrer)) {
            header('Referrer-Policy: ' . $referrer);
        }

        $permissions = $this->settings->get('header_permissions_policy', '');
        if (!empty($permissions)) {
            header('Permissions-Policy: ' . $permissions);
        }

        $csp = $this->settings->get('header_csp', '');
        if (!empty($csp)) {
            header('Content-Security-Policy: ' . $csp);
        }
    }

    public function handleLoginUrlRewrite() {
        if (!$this->settings->get('change_login_url', false)) {
            return;
        }

        $slug = sanitize_title($this->settings->get('login_url_slug', ''));
        if (empty($slug)) {
            return;
        }

        $request = isset($_SERVER['REQUEST_URI']) ? trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') : '';

        $blocked = ['wp-login.php', 'wp-register.php'];
        foreach ($blocked as $path) {
            if ($request === $path || strpos($request, $path) === 0) {
                if (!is_user_logged_in()) {
                    $mode = $this->settings->get('login_redirect_mode', '404');
                    if ($mode === '404') {
                        status_header(404);
                        nocache_headers();
                        include get_404_template();
                        exit;
                    }
                    wp_redirect(home_url(), 302);
                    exit;
                }
            }
        }

        if ($request === $slug) {
            require_once ABSPATH . 'wp-login.php';
            exit;
        }

        add_filter('login_url', function ($url) use ($slug) {
            return home_url($slug);
        }, 9999);

        add_filter('logout_url', function ($url) use ($slug) {
            return add_query_arg('action', 'logout', home_url($slug));
        }, 9999);

        add_filter('register_url', function ($url) use ($slug) {
            return add_query_arg('action', 'register', home_url($slug));
        }, 9999);

        add_filter('lostpassword_url', function ($url) use ($slug) {
            return add_query_arg('action', 'lostpassword', home_url($slug));
        }, 9999);
    }
}
