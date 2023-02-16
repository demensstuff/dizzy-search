<?php
    namespace App\Storage\Cache;

    use App\Helpers\FilesystemHelper;
    use App\Entities\Cache\CacheDescr;
    use App\Entities\Cache\WordOffsets;
    use App\Entities\JSONer;
    use App\Storage\Cache\ICacheStorage;

    class FileCacheStorage implements ICacheStorage {
        protected string $cacheDir;
        protected string $indexFile;

        public function __construct() {
            $this->cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/../.cache/content/';
            $this->indexFile = $this->cacheDir . 'index.json';

            if (!is_dir($this->cacheDir)) {
                mkdir($this->cacheDir, 0777, true);
            }
        }

        /**
         * @return CacheDescr[]|null
         */
        public function list() {
            $arr = json_decode(@file_get_contents($this->indexFile), true);
            if (!$arr) {
                return null;
            }

            return CacheDescr::map($arr);
        }

        /**
         * @param string $key
         * @return WordOffsets[]|null
         */
        public function offsets(string $key) {
            $arr = json_decode(@file_get_contents($this->offsetsPath($key)), true);
            if (!$arr) {
                return null;
            }

            return WordOffsets::manyFromArray($arr);
        }

        public function exact(string $key): ?string {
            $str = @file_get_contents($this->exactPath($key));
            if (!$str) {
                return null;
            }

            return $str;
        }

        /**
         * @param string $key
         * @return string[]|null
         */
        public function tokenized(string $key) {
            $arr = json_decode(@file_get_contents($this->tokenizedPath($key)), true);
            if (!$arr) {
                return null;
            }

            return $arr;
        }

        public function raw(string $key): ?string {
            $str = @file_get_contents($this->rawPath($key));
            if (!$str) {
                return null;
            }

            return $str;
        }

        /**
         * @param CacheDescr[] $listDescr
         */
        public function putList($listDescr) {
            file_put_contents($this->indexFile, JSONer::manyToJSON($listDescr));
        }

        /**
         * @param string $key
         * @param WordOffsets[] $offsets
         */
        public function putOffsets(string $key, $offsets) {
            $this->makeCacheDir($key);

            file_put_contents($this->offsetsPath($key), JSONer::manyToJSON($offsets));
        }

        public function putExact(string $key, string $exactCache) {
            $this->makeCacheDir($key);

            file_put_contents($this->exactPath($key), $exactCache);
        }

        /**
         * @param string $key
         * @param string[] $tokenized
         */
        public function putTokenized(string $key, $tokenized) {
            $this->makeCacheDir($key);

            file_put_contents($this->tokenizedPath($key), JSONer::anyToJSON($tokenized));
        }

        public function putRaw(string $key, string $raw) {
            $this->makeCacheDir($key);

            file_put_contents($this->rawPath($key), $raw);
        }

        public function remove(string $key) {
            $dir = $this->getCacheDir($key);

            if (is_dir($dir)) {
                FilesystemHelper::rmdir($this->getCacheDir($key));
            }
        }

        protected function offsetsPath(string $key): string {
            return $this->getCacheDir($key) . '/offsets.json';
        }

        protected function exactPath(string $key): string {
            return $this->getCacheDir($key) . '/exact.txt';
        }

        protected function tokenizedPath(string $key): string {
            return $this->getCacheDir($key) . '/tokenized.json';
        }

        protected function rawPath(string $key): string {
            return $this->getCacheDir($key) . '/raw.txt';
        }

        protected function makeCacheDir(string $key) {
            $dir = $this->getCacheDir($key);

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        protected function getCacheDir(string $key) {
            return $this->cacheDir . $key;
        }
    }
?>