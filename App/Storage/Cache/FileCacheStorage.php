<?php
	namespace App\Storage\Cache;
	
	use App\Entities\Cache\EntityRawCache;
    use App\Helpers\ArrayHelper;
    use App\Helpers\FilesystemHelper;
	use App\Entities\Cache\EntityCacheIndex;
	use App\Entities\Cache\EntityCache;

    /** File repository implementation of cache storage */
	class FileCacheStorage implements ICacheStorage {
        /** @var string Cache root directory */
		protected string $cacheDir;

        /** @var string Cache index file path */
		protected string $indexFile;
		
		public function __construct() {
			$this->cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/../.cache/content/';
			$this->indexFile = $this->cacheDir . 'index.bin';
			
			if (!is_dir($this->cacheDir)) {
				mkdir($this->cacheDir, 0777, true);
			}
		}
		
		/**
		 * @return EntityCacheIndex Full cache index
		 */
		public function list(): EntityCacheIndex {
            return new EntityCacheIndex($this->get($this->indexFile));
		}

		/**
         * @param string $key Cache key
		 * @return EntityCache Cache object
		 */
		public function cache(string $key): EntityCache {
            return new EntityCache($this->get($this->cachePath($key)));
		}

        /**
         * @param string $key Cache key
         * @return EntityRawCache Cache object
         */
		public function raw(string $key): EntityRawCache {
            return new EntityRawCache($this->get($this->rawPath($key)));
		}
		
		/**
         * Saves cache index into storage
		 * @param EntityCacheIndex $index Full cache index
		 */
		public function putList(EntityCacheIndex $index): void {
            $this->put($this->indexFile, $index->toArray());
		}

		/**
         * Saves cache object into storage
         * @param string $key Cache key
         * @param EntityCache $cache Cache object
         */
		public function putCache(string $key, EntityCache $cache): void {
			$this->put($this->cachePath($key, true), $cache->toArray());
		}

		/**
         * Saves raw cache object into storage
         * @param string $key Cache key
         * @param EntityRawCache $rawCache Raw cache object
         */
		public function putRaw(string $key, EntityRawCache $rawCache): void {
            $this->put($this->rawPath($key, true), $rawCache->toArray());
		}

        /**
         * Removes cache object
         * @param string $key Cache key
         * @return void
         */
		public function remove(string $key): void {
			$dir = $this->getCacheDir($key);
			
			if (is_dir($dir)) {
				FilesystemHelper::rmdir($dir);
			}
		}

        /**
         * @param string $key Cache key
         * @param bool $shouldCreate Whether the folder should be created
         * @return string Path to raw cache file
         */
		protected function rawPath(string $key, bool $shouldCreate = false): string {
			return $this->getCacheDir($key, $shouldCreate) . '/raw.bin';
		}

        /**
         * @param string $key Cache key
         * @param bool $shouldCreate Whether the folder should be created
         * @return string Path to cache file
         */
		protected function cachePath(string $key, bool $shouldCreate = false): string {
			return $this->getCacheDir($key, $shouldCreate) . '/cache.bin';
		}

        /**
         * @param string $key Cache key
         * @param bool $shouldCreate Whether the folder should be created
         * @return string Cache folder
         */
		protected function getCacheDir(string $key, bool $shouldCreate = false): string {
			$dir = $this->cacheDir . $key;

            if ($shouldCreate && !is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            return $dir;
		}

        /**
         * Retrieves and deserializes an object from the file
         * @param string $path Object file path
         * @return mixed EntityCache, EntityCacheIndex or EntityRawCache
         */
        protected function get(string $path): mixed {
            $object = @igbinary_unserialize(@file_get_contents($path));

            return $object ?: null;
        }

        /**
         * Stores an object to the file (EntityCache, EntityCacheIndex or EntityRawCache)
         * @param string $path Path to cache file
         * @param array $object Cache object to be stored
         * @param bool $debug Whether to save cache as JSON
         * @return void
         */
        protected function put(string $path, array $object, bool $debug = false): void {
            file_put_contents($path, igbinary_serialize($object));

            if ($debug) {
                file_put_contents($path . '.json', ArrayHelper::toJSON($object));
            }
        }
    }