<?php
    namespace App\Usecases;

    ini_set('log_errors', 1);
    ini_set('error_log', $_SERVER['DOCUMENT_ROOT'] . '/../php-error.log');

    use App\Helpers\FilesystemHelper;
    use App\Helpers\IgnoredWordsHelper;
    use App\Helpers\PageHelper;
    use App\Responses\CacheResponse;
    use App\Responses\Response;
    use App\Services\Cache\CacheInfoService;
    use App\Services\Cache\CacheService;

    class CacheUC {
        protected CacheService $cacheService;

        protected CacheInfoService $cacheInfoService;

        public function __construct() {
            $this->cacheService = new CacheService();
            $this->cacheInfoService = new CacheInfoService();
        }

        public function buildCache(): CacheResponse {
            $ignoredWords = IgnoredWordsHelper::load();
            $includedDirs = $this->cacheInfoService->includedDirs();
            $excludedDirs = $this->cacheInfoService->excludedDirs();
            $excludedPages = $this->cacheInfoService->excludedPages();

            $this->cacheService->prepareCaching($ignoredWords);
            $this->cacheService->cacheFiles($includedDirs, $excludedDirs);
            $this->cacheService->cachePages($excludedPages);
            list ($added, $removed, $unchanged) = $this->cacheService->finishCaching();

            return CacheResponse::ok($added, $removed, $unchanged);
        }

        /**
         * @param string[] $includedDirs
         * @param string[] $excludedDirs
         * @param string[] $excludedPages
         * @return Response
         */
        public function setDirs($includedDirs, $excludedDirs, $excludedPages): Response {
            $includedDirs = FilesystemHelper::onlyExistingDirs($includedDirs);
            $excludedDirs = FilesystemHelper::onlyExistingDirs($excludedDirs);

            if ($includedDirs === null || $excludedDirs === null) {
                return Response::withError(Response::INVALID_INPUT);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, true);

            $domain = PageHelper::domain();
            $numExcludedPages = count($excludedPages);

            for ($i = 0; $i < $numExcludedPages; $i++) {
                if (!PageHelper::isLocalHTMLPage($ch, $domain, $excludedPages[$i])) {
                    return Response::withError(Response::INVALID_INPUT);
                }
            }

            $this->cacheInfoService->putIncludedDirs($includedDirs);
            $this->cacheInfoService->putExcludedDirs($excludedDirs);
            $this->cacheInfoService->putExcludedPages($excludedPages);

            return Response::ok();
        }

        /**
         * @return string[]
         */
        public function includedDirs() {
            return $this->cacheInfoService->includedDirs();
        }

        /**
         * @return string[]
         */
        public function excludedDirs() {
            return $this->cacheInfoService->excludedDirs();
        }

        /**
         * @return string[]
         */
        public function excludedPages() {
            return $this->cacheInfoService->excludedPages();
        }
    }
?>