<?php
	namespace App\Services\Search;
	
	use App\Core\DBException;
    use App\Entities\Search\TopQueries;
	use App\Storage\Search\DBSearchInfoStorage;
	use App\Storage\Search\ISearchInfoStorage;

    /** This class contains methods related to search cache meta information */
	class SearchInfoService {
        /** @var ISearchInfoStorage Search cache info storage */
		protected ISearchInfoStorage $searchInfoStorage;
		
		public function __construct() {
			$this->searchInfoStorage = new DBSearchInfoStorage();
		}

        /**
         * Increments the weight of the given query
         * @param string $str Search query
         * @throws DBException
         */
        public function incQueryWeight(string $str): void {
			$this->searchInfoStorage->incQueryWeight($str);
		}

        /**
         * Returns top search queries by weight
         * @param int $num Number of top search queries
         * @return TopQueries
         * @throws DBException
         */
		public function topQueries(int $num): TopQueries {
			return $this->searchInfoStorage->topQueries($num);
		}
	}