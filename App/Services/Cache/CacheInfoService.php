<?php
	namespace App\Services\Cache;
	
	use App\Core\DBException;
    use App\Storage\Cache\DBCacheInfoStorage;
	use App\Storage\Cache\ICacheInfoStorage;

    /** This class contains methods related to cache settings */
	class CacheInfoService {
        /** @var ICacheInfoStorage Cache info repository  */
		protected ICacheInfoStorage $cacheInfoStorage;
		
		public function __construct() {
			$this->cacheInfoStorage = new DBCacheInfoStorage();
		}

        /**
         * @return string[] Directories to be scanned
         * @throws DBException
         */
		public function includedDirs(): array {
			return $this->cacheInfoStorage->includedDirs();
		}

        /**
         * @return string[] Directories to be ignored
         * @throws DBException
         */
		public function excludedDirs(): array {
			return $this->cacheInfoStorage->excludedDirs();
		}

        /**
         * @return string[] Web pages to be ignored
         * @throws DBException
         */
		public function excludedPages(): array {
			return $this->cacheInfoStorage->excludedPages();
		}

        /**
         * @param string[] $includedDirs Included directories to be stored
         * @throws DBException
         */
		public function putIncludedDirs(array $includedDirs): void {
			$this->cacheInfoStorage->putIncludedDirs($includedDirs);
		}

        /**
         * @param string[] $excludedDirs Excluded directories to be stored
         * @throws DBException
         */
		public function putExcludedDirs(array $excludedDirs): void {
			$this->cacheInfoStorage->putExcludedDirs($excludedDirs);
		}

        /**
         * @param string[] $excludedPages Excluded pages to be stored
         * @throws DBException
         */
		public function putExcludedPages(array $excludedPages): void {
			$this->cacheInfoStorage->putExcludedPages($excludedPages);
		}
	}