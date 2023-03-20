<?php
	namespace App\UseCases;
	
	ini_set('log_errors', 1);
	ini_set('error_log', $_SERVER['DOCUMENT_ROOT'] . '/../.cache/php-error.log');

    use App\Core\DBException;
    use App\Helpers\FilesystemHelper;
	use App\Helpers\IgnoredWordsHelper;
	use App\Helpers\WebHelper;
	use App\Responses\CacheResponse;
	use App\Responses\Response;
	use App\Services\Cache\CacheInfoService;
	use App\Services\Cache\CacheService;

    /** This class contains Cache scenarios */
	class CacheUC {
        /** @var CacheService Cache service */
		protected CacheService $cacheService;

        /** @var CacheInfoService Cache info service (included/excluded dirs or pages) */
		protected CacheInfoService $cacheInfoService;
		
		public function __construct() {
			$this->cacheService = new CacheService();
			$this->cacheInfoService = new CacheInfoService();
		}

        /**
         * This function builds a complete cache of valid files and pages
         * @return CacheResponse
         * @throws DBException
         */
		public function buildCache(): CacheResponse {
			$ignoredWords = IgnoredWordsHelper::load();
			
			$includedDirs = $this->includedDirs();
			$excludedDirs = $this->excludedDirs();
			$excludedPages = $this->excludedPages();
			
			$this->cacheService->prepareCaching($ignoredWords);
			$this->cacheService->cacheFiles($includedDirs, $excludedDirs);
			$this->cacheService->cachePages($excludedPages);
			list ($added, $removed, $unchanged, $time) = $this->cacheService->finish();
			
			return CacheResponse::ok($added, $removed, $unchanged, $time);
		}

        /**
         * Stores all the settings related to cache
         * @param string[] $includedDirs array of included directories
         * @param string[] $excludedDirs array of excluded directories
         * @param string[] $excludedPages array of excluded pages
         * @return Response
         * @throws DBException
         */
		public function setDirs(
            array $includedDirs,
            array $excludedDirs,
            array $excludedPages
        ): Response {
			$includedDirs = FilesystemHelper::onlyExistingDirs($includedDirs);
			$excludedDirs = FilesystemHelper::onlyExistingDirs($excludedDirs);
			
			if ($includedDirs === null || $excludedDirs === null) {
				return Response::withError(Response::INVALID_INPUT);
			}
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_HEADER, true);
			
			$domain = WebHelper::domain();
			$numExcludedPages = count($excludedPages);
			
			for ($i = 0; $i < $numExcludedPages; $i++) {
				if (!WebHelper::isLocalHTMLPage($ch, $domain, $excludedPages[$i])) {
					return Response::withError(Response::INVALID_INPUT);
				}
			}
			
			$this->cacheInfoService->putIncludedDirs($includedDirs);
			$this->cacheInfoService->putExcludedDirs($excludedDirs);
			$this->cacheInfoService->putExcludedPages($excludedPages);
			
			return Response::ok();
		}

        /**
         * @return string[] Array of included directories
         * @throws DBException
         */
		public function includedDirs(): array {
			return $this->cacheInfoService->includedDirs();
		}

        /**
         * @return string[] Array of excluded directories
         * @throws DBException
         */
		public function excludedDirs(): array {
			return $this->cacheInfoService->excludedDirs();
		}

        /**
         * @return string[] Array of excluded pages
         * @throws DBException
         */
		public function excludedPages(): array {
			return $this->cacheInfoService->excludedPages();
		}
	}