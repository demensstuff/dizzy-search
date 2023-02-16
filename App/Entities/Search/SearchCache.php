<?php
    namespace App\Entities\Search;

    use App\Entities\JSONer;

    class SearchCache extends JSONer {
        public string $str;

        public int $time;

        /** @var SearchResult[] $results */
        public $results;

        /**
         * @var string $str;
         * @var int $time;
         * $var SearchResult[] $results
         */
        public function __construct($str, $time, $results) {
            $this->str     = $str;
            $this->time    = $time;
            $this->results = $results;
        }

        /**
         * @return array
         */
        public function toArray() {
            return [
                'str'     => $this->str,
                'time'    => $this->time,
                'results' => JSONer::manyToArray($this->results)
            ];
        }
    }
?>