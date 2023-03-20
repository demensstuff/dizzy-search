<?php
    /** Simple autoloader for App classes */
	class Autoloader {
		const NAMESPACE_PREFIX = "App";
		
		protected static string $pathPrefix;

        /** Initializes the autoloader */
		public static function init(): void {
			self::$pathPrefix = $_SERVER['DOCUMENT_ROOT'] . '/../';
		}

        /**
         * @param string $classPath Class namespace
         * @return void
         */
		public static function load(string $classPath): void {
			if (str_starts_with($classPath, self::NAMESPACE_PREFIX)) {		
				require_once(self::$pathPrefix . str_replace('\\', '/', $classPath) . '.php');
			}
		}
	}

	Autoloader::init();
	spl_autoload_register('Autoloader::load');
