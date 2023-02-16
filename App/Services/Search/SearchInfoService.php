<?php
    namespace App\Services\Search;

    use App\Entities\Search\TopQuery;
    use App\Storage\Search\DBSearchInfoStorage;
    use App\Storage\Search\ISearchInfoStorage;

    class SearchInfoService {
        protected ISearchInfoStorage $searchInfoStorage;

        public function __construct() {
            $this->searchInfoStorage = new DBSearchInfoStorage();
        }

        public function incQueryWeight(string $str) {
            $this->searchInfoStorage->incQueryWeight($str);
        }

        /**
         * @param int $num
         * @return TopQuery[]
         */
        public function topQueries(int $num) {
            return $this->searchInfoStorage->topQueries($num);
        }
    }
?>