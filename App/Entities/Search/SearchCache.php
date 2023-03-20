<?php

namespace App\Entities\Search;

use App\Entities\Cache\EntityCacheIndex;
use App\Helpers\LangHelper;

/** This class contains a cached search result */
    class SearchCache {
        /** @var string Array key 'name': cached entity name */
        public const NAME = 'name';

        /** @var string Array key 'url': cached entity URL */
        public const URL = 'url';

        /** @var string Array key 'type': cached entity type
         * (EntityCacheIndex::TYPE_PAGE or EntityCacheIndex::TYPE_FILE)
         */
        public const TYPE = 'type';

        /** @var string Array key 'lang': language of the cached entity */
        public const LANG = 'lang';

        /** @var string Array key 'key': unique search cache key */
        public const KEY = 'key';

        /** @var string Array key 'num_matches': number of the longest matches */
        public const NUM_MATCHES = 'num_matches';

        /** @var string Array key 'is_exact': is exact match */
        public const IS_EXACT = 'is_exact';

        /** @var string Array key 'longest_match': length of the best match */
        public const LONGEST_MATCH = 'longest_match';

        /** @var string Longest match starting position */
        public const MATCH_START = 'match_start';

        /** @var string Longest match ending position */
        public const MATCH_END = 'match_end';

        /** @var string Array key 'substr': raw cache substring containing best match */
        public const SUBSTR = 'substr';

        /** @var string Search string */
		protected string $str = '';

        /** @var int Timestamp of the search */
        protected int $time = 0;

        /** @var array Search results container */
        protected array $arr = [];

        /** @var int Number of search results */
        protected int $numResults = 0;

		public function __construct(?array $arr = null) {
			$this->str  = $arr['str'] ?? '';
			$this->time = $arr['time'] ?? 0;
			$this->arr  = $arr['results'] ?? [];

            $this->numResults = count($this->arr);
		}

        /**
         * Clears the cache, sets the query string and time of the cache
         * @param string $str Query string
         * @return void
         */
        public function reset(string $str): void {
            $this->str = $str;
            $this->time = time();
            $this->arr = [];

            $this->numResults = 0;
        }
		
		/**
		 * @return array Search cache as an array
		 */
		public function toArray(): array {
			return [
				'str'     => $this->str,
				'time'    => $this->time,
				'results' => $this->arr
			];
		}

        /**
         * @return array Results as an array
         */
        public function results(): array {
            return $this->arr;
        }

        /**
         * Paginates the search result
         * @param int $page Page number
         * @param int $perPage Items per page
         * @return array
         */
        public function paginate(int $page, int $perPage): array {
            return array_slice($this->arr, ($page - 1)*$perPage, $perPage);
        }

        /**
         * Removes non-exact results from the array
         * @return void
         */
        public function removeNonExact(): void {
            $this->arr = array_filter($this->arr, function (array $value) {
                return $value[self::IS_EXACT];
            });

            $this->numResults = count($this->arr);
        }

        /**
         * @return int Number of search results
         */
        public function numResults(): int {
            return $this->numResults;
        }

        /**
         * Leaves only first $limit results
         * @param int $limit Number of results
         * @return void
         */
        public function slice(int $limit): void {
            $this->arr = array_slice($this->arr, 0, $limit);

            if ($limit < $this->numResults) {
                $this->numResults = $limit;
            }
        }

        /**
         * Sorts the results. The order is the following:
         * is exact -> current lang -> type (page > file) -> longest match -> num matches
         * @param ?string $lang Current language
         * @return void
         */
        public function sort(?string $lang = null): void {
            usort($this->arr, function (array $a, array $b) use ($lang) {
                if ($a[self::TYPE] != $b[self::TYPE]) {
                    if ($b[self::TYPE] == EntityCacheIndex::TYPE_PAGE) {
                        return 1;
                    }

                    return -1;
                }

                if ($a[self::IS_EXACT] != $b[self::IS_EXACT]) {
                    return $b[self::IS_EXACT] <=> $a[self::IS_EXACT];
                }

                if ($lang) {
                    $aL = $a[self::LANG] == $lang || $a[self::LANG] == LangHelper::NONE;
                    $bL = $b[self::LANG] == $lang || $b[self::LANG] == LangHelper::NONE;

                    if ($aL != $bL) {
                        if ($bL) {
                            return 1;
                        }

                        return -1;
                    }
                }

                if ($a[self::LONGEST_MATCH] != $b[self::LONGEST_MATCH]) {
                    return $b[self::LONGEST_MATCH] <=> $a[self::LONGEST_MATCH];
                }

                return $b[self::NUM_MATCHES] <=> $a[self::NUM_MATCHES];
            });
        }

        /**
         * @return int Search cache creation time
         */
        public function time(): int {
            return $this->time;
        }

        /**
         * Adds search result to the cache
         * @param string $name Cached entity name
         * @param string $url Cached entity URL
         * @param string $type Cached entity type
         * (EntityCacheIndex::TYPE_PAGE or EntityCacheIndex::TYPE_FILE)
         * @param string $lang Language of the cached entity
         * @param string $key Unique search cache key
         * @param int $numMatches Number of the longest match
         * @param bool $isExact Is exact match
         * @param int $longestMatch Length of the best match
         * @param int $matchStart Longest match starting position
         * @param int $matchEnd Longest match ending position
         * @return void
         */
        public function add(
            string $name,
            string $url,
            string $type,
            string $lang,
            string $key,
            int $numMatches,
            bool $isExact,
            int $longestMatch,
            int $matchStart,
            int $matchEnd
        ): void {
            $this->arr[$key] = [
                self::NAME          => $name,
                self::URL           => $url,
                self::TYPE          => $type,
                self::LANG          => $lang,
                self::KEY           => $key,
                self::NUM_MATCHES   => $numMatches,
                self::IS_EXACT      => $isExact,
                self::LONGEST_MATCH => $longestMatch,
                self::MATCH_START   => $matchStart,
                self::MATCH_END     => $matchEnd
            ];

            $this->numResults++;
        }

        /**
         * Iterates over all search results and applies the callback to them
         * @param callable $cb Callback function
         * @return void
         */
        public function each(callable $cb): void {
            foreach ($this->arr as &$value) {
                $cb($value);
            }
        }
	}