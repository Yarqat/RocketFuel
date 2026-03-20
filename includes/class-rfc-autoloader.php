<?php
defined('ABSPATH') || exit;

final class RFC_Autoloader {

    private static $map = [];
    private static $dirs = [];

    public static function init() {
        self::$dirs = [
            RFC_PATH . 'includes/',
            RFC_PATH . 'includes/cache/',
            RFC_PATH . 'includes/optimization/',
            RFC_PATH . 'includes/server/',
            RFC_PATH . 'includes/database/',
            RFC_PATH . 'includes/cdn/',
            RFC_PATH . 'includes/media/',
            RFC_PATH . 'includes/heartbeat/',
            RFC_PATH . 'includes/cleanup/',
            RFC_PATH . 'includes/security/',
            RFC_PATH . 'includes/monitoring/',
            RFC_PATH . 'includes/abstracts/',
            RFC_PATH . 'admin/',
        ];

        if (is_dir(RFC_PATH . 'pro/')) {
            self::$dirs[] = RFC_PATH . 'pro/';
            self::$dirs[] = RFC_PATH . 'pro/modules/';
            self::$dirs[] = RFC_PATH . 'pro/admin/';
        }

        spl_autoload_register([__CLASS__, 'resolve']);
    }

    public static function resolve($class) {
        if (strpos($class, 'RFC_') !== 0) {
            return;
        }

        if (isset(self::$map[$class])) {
            require_once self::$map[$class];
            return;
        }

        $filename = 'class-' . strtolower(str_replace('_', '-', $class)) . '.php';

        foreach (self::$dirs as $dir) {
            $filepath = $dir . $filename;
            if (file_exists($filepath)) {
                self::$map[$class] = $filepath;
                require_once $filepath;
                return;
            }
        }
    }
}
