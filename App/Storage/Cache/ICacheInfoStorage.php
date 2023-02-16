<?php
    namespace App\Storage\Cache;

    interface ICacheInfoStorage {
        /**
         * @return string[]
         */
        public function includedDirs();

        /**
         * @return string[]
         */
        public function excludedDirs();

        /**
         * @return string[]
         */
        public function excludedPages();

        /**
         * @param string[] $includedDirs
         */
        public function putIncludedDirs($includedDirs);

        /**
         * @param string[] $excludedDirs
         */
        public function putExcludedDirs($excludedDirs);

        /**
         * @param string[] $excludedPages
         */
        public function putExcludedPages($excludedPages);
    }
?>