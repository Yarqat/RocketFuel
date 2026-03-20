<?php
defined('ABSPATH') || exit;

final class RFC_Pro_Guard {

    private $settings;
    private $degradation_key = 'rfc_pro_degradation';

    private $feature_tiers = [
        'basic' => [
            'critical_css_enabled',
            'delay_js',
            'remove_unused_css',
            'image_auto_optimize',
            'image_webp',
            'image_avif',
        ],
        'advanced' => [
            'script_manager_enabled',
            'local_analytics',
            'local_gtm',
            'instant_page',
            'cloudflare_enabled',
            'bunnycdn_enabled',
            'varnish_enabled',
            'sucuri_enabled',
            'white_label_enabled',
            'monitoring_enabled',
            'weekly_email_report',
            'wc_auto_exclude',
            'wc_defer_cart_fragments',
            'wc_disable_scripts_nonshop',
            'change_login_url',
            'security_headers_enabled',
        ],
    ];

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;
        add_action('admin_init', [$this, 'checkDegradation']);
        add_action('rfc_license_expired', [$this, 'onLicenseExpired']);
        add_action('rfc_license_activated', [$this, 'onLicenseActivated']);
        add_action('rfc_trial_expired', [$this, 'onTrialExpired']);
    }

    public function checkDegradation() {
        if (!RFC_Engine::instance() || !RFC_Engine::instance()->hasPro()) {
            return;
        }

        $pro = RFC_Engine::instance()->module('pro');
        if (!$pro || !method_exists($pro, 'isLicensed')) {
            return;
        }

        $license = $this->getLicense();
        if (!$license) {
            return;
        }

        $grace = $license->getGraceLevel();
        $current = (int) get_option($this->degradation_key, 0);

        if ($grace === $current) {
            return;
        }

        update_option($this->degradation_key, $grace, false);
        $this->applyDegradation($grace);
    }

    private function applyDegradation($level) {
        switch ($level) {
            case 0:
                break;

            case 1:
                $this->showNotice(
                    'warning',
                    'RocketFuel Pro: License validation pending. All features are active. ' .
                    'If this persists, check your internet connection or contact support.'
                );
                break;

            case 2:
                $this->disableFeatures($this->feature_tiers['advanced']);
                $this->showNotice(
                    'warning',
                    'RocketFuel Pro: License could not be verified for 7+ days. ' .
                    'Advanced features (Script Manager, CDN integrations, WooCommerce, Analytics) have been paused. ' .
                    'Core Pro features (Critical CSS, Image Optimization) are still active. ' .
                    'Please verify your license to restore all features.'
                );
                break;

            case 3:
                $this->disableFeatures(array_merge(
                    $this->feature_tiers['basic'],
                    $this->feature_tiers['advanced']
                ));
                $this->showNotice(
                    'error',
                    'RocketFuel Pro: License expired or could not be verified for 14+ days. ' .
                    'All Pro features have been paused. Your site is running on the free version — ' .
                    'nothing is broken. Renew your license to restore Pro features.'
                );
                break;

            case 4:
                $this->disableFeatures(array_merge(
                    $this->feature_tiers['basic'],
                    $this->feature_tiers['advanced']
                ));
                break;
        }
    }

    private function disableFeatures($features) {
        $snapshot_key = 'rfc_pro_settings_backup';
        $backup = get_option($snapshot_key, []);

        foreach ($features as $key) {
            $current = $this->settings->get($key);
            if ($current) {
                $backup[$key] = $current;
                $this->settings->set($key, false);
            }
        }

        update_option($snapshot_key, $backup, false);
        $this->settings->save();
    }

    private function restoreFeatures() {
        $snapshot_key = 'rfc_pro_settings_backup';
        $backup = get_option($snapshot_key, []);

        if (empty($backup)) {
            return;
        }

        foreach ($backup as $key => $value) {
            $this->settings->set($key, $value);
        }

        delete_option($snapshot_key);
        $this->settings->save();
    }

    public function onLicenseExpired() {
        $this->applyDegradation(3);
        update_option($this->degradation_key, 3, false);
    }

    public function onLicenseActivated() {
        $this->restoreFeatures();
        update_option($this->degradation_key, 0, false);
        delete_option('rfc_pro_settings_backup');

        $this->showNotice(
            'success',
            'RocketFuel Pro: License activated! All Pro features have been restored.'
        );
    }

    public function onTrialExpired() {
        $this->disableFeatures(array_merge(
            $this->feature_tiers['basic'],
            $this->feature_tiers['advanced']
        ));

        update_option($this->degradation_key, 3, false);

        $this->showNotice(
            'info',
            'Your RocketFuel Pro trial has ended. All Pro features have been paused — ' .
            'your site is still running perfectly on the free version. ' .
            'Upgrade to Pro to restore all features. Your settings are saved.'
        );
    }

    public function isFeatureAllowed($feature_key) {
        $grace = (int) get_option($this->degradation_key, 0);

        if ($grace === 0) {
            return true;
        }

        if ($grace >= 3) {
            return false;
        }

        if ($grace >= 2 && in_array($feature_key, $this->feature_tiers['advanced'], true)) {
            return false;
        }

        return true;
    }

    public function getDegradationStatus() {
        $level = (int) get_option($this->degradation_key, 0);
        $labels = [
            0 => 'fully_active',
            1 => 'validation_pending',
            2 => 'advanced_paused',
            3 => 'all_pro_paused',
            4 => 'permanent_free',
        ];
        return [
            'level' => $level,
            'status' => $labels[$level] ?? 'unknown',
            'features_paused' => $this->getPausedFeatures($level),
        ];
    }

    private function getPausedFeatures($level) {
        if ($level >= 3) {
            return array_merge($this->feature_tiers['basic'], $this->feature_tiers['advanced']);
        }
        if ($level >= 2) {
            return $this->feature_tiers['advanced'];
        }
        return [];
    }

    private function showNotice($type, $message) {
        set_transient('rfc_pro_notice', ['type' => $type, 'message' => $message], 86400);
    }

    private function getLicense() {
        if (!class_exists('RFC_License')) {
            return null;
        }

        static $license = null;
        if ($license === null) {
            $license = new RFC_License('rocketfuel-cache');
        }
        return $license;
    }
}
