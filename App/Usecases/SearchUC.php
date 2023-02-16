<?php
    namespace App\Usecases;

    ini_set('log_errors', 1);
    ini_set('error_log', $_SERVER['DOCUMENT_ROOT'] . '/../php-error.log');

    use App\Entities\Search\SearchQuery;
    use App\Entities\Search\SearchResult;
    use App\Entities\Search\TopQuery;
    use App\Helpers\IgnoredWordsHelper;
    use App\Helpers\LangHelper;
    use App\Responses\Response;
    use App\Responses\SearchResponse;
    use App\Services\Search\SearchInfoService;
    use App\Services\Search\SearchService;

    class SearchUC {
        /** Max words in a search query */
        const WORD_LIMIT = 20;

        /** Max chars in a search query */
        const CHAR_LIMIT = 128;

        protected SearchService $searchService;
        protected SearchInfoService $searchInfoService;

        public function __construct() {
            $this->searchService = new SearchService();
            $this->searchInfoService = new SearchInfoService();
        }

        public function search(string $str, int $page, int $perPage): SearchResponse {
            if (mb_strlen($str) > self::CHAR_LIMIT) {
                return SearchResponse::withError(Response::INVALID_INPUT);
            }

            $query = new SearchQuery($str, IgnoredWordsHelper::load());

            if (empty($query->str)) {
                return SearchResponse::withError(Response::EMPTY_INPUT);
            }

            if ($query->isUnspecified) {
                return SearchResponse::withError(Response::UNSPECIFIED_INPUT);
            }

            if ($query->numWordsStr > self::WORD_LIMIT) {
                return SearchResponse::withError(Response::INVALID_INPUT);
            }

            /** @var SearchResult[] $results */
            $results = $this->searchService->search($query);

            if ($query->onlyExact) {
                $results = array_filter($results, function ($value) {
                    return $value->isExact;
                });
            }

            if (!$results) {
                return SearchResponse::withError(Response::EMPTY_RESPONSE);
            }

            $this->searchInfoService->incQueryWeight($query->str);

            return self::buildSearchResponse($results, $page, $perPage);
        }

        /**
         * @param int $num
         * @return TopQuery[]
         */
        public function topQueries(int $num) {
            return $this->searchInfoService->topQueries($num);
        }

        /**
         * @param SearchResult[] $results
         * @param int $page
         * @param int $perPage
         * @return SearchResponse
         */
        protected static function buildSearchResponse(
            $results,
            int $page,
            int $perPage
        ): SearchResponse {
            SearchService::sortResults($results, LangHelper::lang());

            $numResults = count($results);
            if ($numResults <= $perPage) {
                return SearchResponse::ok($results, 1, 1, 1, $numResults);
            }

            $maxPage = ($numResults - 1) / $perPage + 1;

            if ($page < 1 || $page > $maxPage) {
                $page = 1;
            }

            $results = SearchResult::paginate($results, $page, $perPage);

            $firstIndex = ($page - 1)*$perPage + 1;
-            $lastIndex = $firstIndex + count($results) - 1;

            return SearchResponse::ok($results, $page, $maxPage, $firstIndex, $lastIndex);
        }
    }
?>