<?php
    namespace App\Responses;

    use App\Entities\Search\SearchResult;

    class SearchResponse extends Response {
        /** @var SearchResult[] $results */
        public $results;

        public int $page;

        public int $maxPage;

        public int $firstIndex;

        public int $lastIndex;

        /**
         * @return array
         */
        public function toArray() {
            $arr = parent::toArray();

            $arr['results']     = JSONer::manyToArray($this->results);
            $arr['page']        = $this->page;
            $arr['maxPage']     = $this->maxPage;
            $arr['first_index'] = $this->firstIndex;
            $arr['last_index']  = $this->lastIndex;

            return $arr;
        }

        /**
         * @param SearchResult[] $results
         * @param int $page
         * @param int $maxPage
         * @param int $firstIndex
         * @param int $lastIndex
         * @return SearchResponse
         */
        public static function ok(
            $results        = null,
            int $page       = 0,
            int $maxPage    = 0,
            int $firstIndex = 0,
            int $lastIndex  = 0
        ): static {
            $r = parent::ok();

            $r->results    = $results;
            $r->page       = $page;
            $r->maxPage    = $maxPage;
            $r->firstIndex = $firstIndex;
            $r->lastIndex  = $lastIndex;

            return $r;
        }
    }
?>