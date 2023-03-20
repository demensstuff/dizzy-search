<?php
	namespace App\UseCases;

	ini_set('log_errors', 1);
	ini_set('error_log', $_SERVER['DOCUMENT_ROOT'] . '/../.cache/php-error.log');

    use App\Core\DBException;
    use App\Entities\Search\SearchQuery;
	use App\Entities\Search\TopQueries;
	use App\Entities\Search\SearchCache;
	use App\Helpers\IgnoredWordsHelper;
	use App\Helpers\LangHelper;
	use App\Responses\Response;
	use App\Responses\SearchResponse;
	use App\Services\Search\SearchInfoService;
	use App\Services\Search\SearchService;

    /** This class contains Search scenarios */
	class SearchUC {
        /** @var int Max words in a search query */
		protected const WORD_LIMIT = 20;
		
		/** @var int Max chars in a search query */
		protected const CHAR_LIMIT = 128;

        /** @var SearchService Search service */
		protected SearchService $searchService;

        /** @var SearchInfoService Search meta information service */
		protected SearchInfoService $searchInfoService;
		
		public function __construct() {
			$this->searchService = new SearchService();
			$this->searchInfoService = new SearchInfoService();
		}

        /**
         * Performs search by the given parameters
         * @param string $queryStr Raw search query string
         * @param int $page Page number
         * @param int $perPage Results per page
         * @return SearchResponse An object containing all the information about results
         * @throws DBException
         */
		public function search(string $queryStr, int $page, int $perPage): SearchResponse {
			if (mb_strlen($queryStr) > self::CHAR_LIMIT) {
				return SearchResponse::withError(Response::INVALID_INPUT);
			}
			
			$query = new SearchQuery($queryStr, IgnoredWordsHelper::load());
            $queryStr = $query->str();

			if (empty($queryStr)) {
				return SearchResponse::withError(Response::EMPTY_INPUT);
			}
			
			if ($query->isUnspecified()) {
				return SearchResponse::withError(Response::UNSPECIFIED_INPUT);
			}
			
			if ($query->numWordsExact() > self::WORD_LIMIT) {
				return SearchResponse::withError(Response::INVALID_INPUT);
			}

            $searchCache = $this->searchService->search($query);
			if ($query->onlyExact()) {
                $searchCache->removeNonExact();
			}
			
			if ($searchCache->numResults() == 0) {
				return SearchResponse::withError(Response::EMPTY_RESPONSE);
			}
			
			$this->searchInfoService->incQueryWeight($queryStr);
			
			return self::buildSearchResponse($searchCache, $page, $perPage);
		}

        /**
         * Returns top search queries by weight
         * @param int $num Number of top search queries
         * @return TopQueries
         * @throws DBException
         */
		public function topQueries(int $num): TopQueries {
			return $this->searchInfoService->topQueries($num);
		}
		
		/**
         * This function prepares search response based on search results and pagination
		 * @param SearchCache $searchCache Search cache object which contains the results
		 * @param int $page Page number
		 * @param int $perPage Results per page
		 * @return SearchResponse
		 */
		protected static function buildSearchResponse(
			SearchCache $searchCache,
			int         $page,
			int         $perPage
		): SearchResponse {
            $searchCache->sort(LangHelper::lang());
			
			$numResults = $searchCache->numResults();
			if ($numResults <= $perPage) {
				return SearchResponse::ok($searchCache->results(), 1, 1, 1, $numResults);
			}
			
			$maxPage = intdiv(($numResults - 1), $perPage) + 1;
			
			if ($page < 1 || $page > $maxPage) {
				$page = 1;
			}
			
			$resultsArr = $searchCache->paginate($page, $perPage);
			
			$firstIndex = ($page - 1)*$perPage + 1;
			$lastIndex = $firstIndex + count($resultsArr) - 1;

			return SearchResponse::ok($resultsArr, $page, $maxPage, $firstIndex, $lastIndex);
		}
	}