<?php
    namespace App\Storage\Search;

    use App\Entities\Search\TopQuery;

    interface ISearchInfoStorage {
        public function incQueryWeight(string $str);

        /**
         * @param int $num
         * @return TopQuery[]
         */
        public function topQueries(int $num);
    }
?>