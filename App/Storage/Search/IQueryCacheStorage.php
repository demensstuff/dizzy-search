<?php
    namespace App\Storage\Search;

    use App\Entities\Search\SearchCache;
    use App\Entities\Search\SearchResult;

    interface IQueryCacheStorage {
        /**
         * @var string $str
         * @var SearchResult[] $results
         */
        public function put(string $str, $results);

        public function get(string $str): ?SearchCache;

        public function delete(string $str);
    }
?>