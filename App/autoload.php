<?php
    class Autoloader {
        const NAMESPACE_PREFIX = "App";

        protected static string $pathPrefix;

        public static function init() {
            self::$pathPrefix = $_SERVER['DOCUMENT_ROOT'] . '/../';
        }

        public static function load($classPath) {
            if (str_starts_with($classPath, self::NAMESPACE_PREFIX)) {
                require_once(self::$pathPrefix . $classPath . '.php');
            }
        }
    }

    Autoloader::init();
    spl_autoload_register('Autoloader::load');
?>