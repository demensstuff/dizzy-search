<?php
	namespace App\Storage\Search;
	
	use App\Entities\Search\SearchCache;
    use App\Helpers\ArrayHelper;

    /** File repository implementation of search cache storage */
	class FileQueryCacheStorage implements IQueryCacheStorage {
        /** @var string Search cache root directory */
		protected string $cacheDir;
		
		public function __construct() {
			$this->cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/../.cache/queries/';
			
			if (!is_dir($this->cacheDir)) {
				mkdir($this->cacheDir, 0777, true);
			}
		}
		
		/**
         * Stores search cache
		 * @var string $str Search query (identifier)
		 * @var SearchCache $searchCache Search cache object
         * @var bool $debug Whether to save cache as JSON
		 */
		public function put(
            string $str,
            SearchCache $searchCache,
            bool $debug = false
        ): void {
            $path = $this->path($str);
            $arr = $searchCache->toArray();

			file_put_contents($path, igbinary_serialize($arr));

            if ($debug) {
                file_put_contents($path . '.json', ArrayHelper::toJSON($arr));
            }
		}

        /**
         * Retrieves search cache from the storage
         * @param string $str Search query (identifier)
         * @return SearchCache
         */
		public function get(string $str): SearchCache {
            $arr = @igbinary_unserialize(@file_get_contents($this->path($str)));

            return new SearchCache($arr ?: null);
		}

        /**
         * Removes stored search cache
         * @param string $str Search query (identifier)
         * @return void
         */
		public function delete(string $str): void {
			$path = $this->path($str);

			if (file_exists($path)) {
				unlink($path);
			}
		}

        /**
         * @param string $str Search query (identifier)
         * @return string Search cache path
         */
		protected function path(string $str): string {
			return $this->cacheDir . md5($str) . '.bin';
		}
	}