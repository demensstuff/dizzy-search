<?php
	namespace App\Helpers;

    /** This class contains helpful functions to interact with the file system */
	class FilesystemHelper {
		/**
         * This function returns the list of all files and directories in a directory
		 * @param string $dir Directory to be scanned
		 * @return string[] Names of files and directories
		 */
		public static function listFiles(string $dir): array {
			return array_diff(scandir($dir), [ '.', '..' ]);
		}

        /**
         * Recursively removes the directory and its subdirectories
         * @param string $dir Directory to be removed
         * @return void
         */
		public static function rmdir(string $dir): void {
			$files = self::listFiles($dir);
			
			foreach ($files as $f) {
				$path = $dir . '/' . $f;
				
				if (is_dir($path)) {
					self::rmdir($path);
				}
				
				unlink($path);
			}
			
			rmdir($dir);
		}
		
		/**
         * Checks if all directory paths in the array are valid
		 * @param string[] $dirs Array of directory paths
		 * @return ?string[] Cleaned array or null
		 */
		public static function onlyExistingDirs(array $dirs): ?array {
			$res = [];
			
			foreach ($dirs as $dir) {
				$dir = trim($dir);
				
				if (!$dir || !is_dir($_SERVER['DOCUMENT_ROOT'] . $dir)) {
					return null;
				}
				
				if (!in_array($dir, $res)) {
					$res[] = $dir;
				}
			}
			
			return $res;
		}
	}