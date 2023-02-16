<?php
    namespace App\Services\Cache;

    use App\Storage\Cache\DBCacheInfoStorage;
    use App\Storage\Cache\ICacheInfoStorage;

    class CacheInfoService {
        protected ICacheInfoStorage $cacheInfoStorage;

        public function __construct() {
            $this->cacheInfoStorage = new DBCacheInfoStorage();
        }

        /**
         * @return string[]
         */
        public function includedDirs() {
            return $this->cacheInfoStorage->includedDirs();
        }

        /**
         * @return string[]
         */
        public function excludedDirs() {
            return $this->cacheInfoStorage->excludedDirs();
        }

        /**
         * @return string[]
         */
        public function excludedPages() {
            return $this->cacheInfoStorage->excludedPages();
        }

        /**
         * @param string[] $includedDirs
         */
        public function putIncludedDirs($includedDirs) {
            $this->cacheInfoStorage->putIncludedDirs($includedDirs);
        }

        /**
         * @param string[] $excludedDirs
         */
        public function putExcludedDirs($excludedDirs) {
            $this->cacheInfoStorage->putExcludedDirs($excludedDirs);
        }

        /**
         * @param string[] $excludedPages
         */
        public function putExcludedPages($excludedPages) {
            $this->cacheInfoStorage->putExcludedPages($excludedPages);
        }
    }
?>