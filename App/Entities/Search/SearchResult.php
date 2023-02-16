<?php
    namespace App\Entities\Search;

    use App\Entities\JSONer;

    class SearchResult extends JSONer {
        public string $name;

        public string $url;

        public string $type;

        public string $lang;

        public string $key;

        public int $numMatches;

        public bool $isExact;

        public int $longestMatch;

        public string $substr;

        public function __construct(
            string $name,
            string $url,
            string $type,
            string $lang,
            string $key,
            int $numMatches,
            bool $isExact,
            int $longestMatch,
            string $substr
        ) {
            $this->name         = $name;
            $this->url          = $url;
            $this->type         = $type;
            $this->lang         = $lang;
            $this->key          = $key;
            $this->numMatches   = $numMatches;
            $this->isExact      = $isExact;
            $this->longestMatch = $longestMatch;
            $this->substr       = $substr;
        }

        /**
         * return array
         */
        public function toArray() {
            return [
                'name'          => $this->name,
                'url'           => $this->url,
                'type'          => $this->type,
                'lang'          => $this->lang,
                'key'           => $this->key,
                'num_matches'   => $this->numMatches,
                'is_exact'      => $this->isExact,
                'longest_match' => $this->longestMatch,
                'substr'        => $this->substr
            ];
        }

        /**
         * @param array $arr
         * @return SearchResult[]
         */
        public static function manyFromArray($arr) {
            return array_map(function ($value) {
                return new self(
                    $value['name'],
                    $value['url'],
                    $value['type'],
                    $value['lang'],
                    $value['key'],
                    $value['num_matches'],
                    $value['is_exact'],
                    $value['longest_match'],
                    $value['substr']
                );
            }, $arr);
        }

        /**
         * @param SearchResult[] $results
         * @param int $page
         * @param int $perPage
         * @return SearchResult[] $results
         */
        public static function paginate($results, int $page, int $perPage) {
            return array_slice($results, ($page - 1)*$perPage, $perPage);
        }
    }
?>