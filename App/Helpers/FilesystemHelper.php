<?php
    namespace App\Helpers;

    class FilesystemHelper {
        /**
         * @param string $dir
         * @return string[]
         */
        public static function listFiles(string $dir) {
            return array_diff(scandir($dir), [ '.', '..' ]);
        }

        public static function rmdir(string $dir) {
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
         * @param string[] $dirs
         * @return string[]|null
         */
        public static function onlyExistingDirs($dirs) {
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
?>