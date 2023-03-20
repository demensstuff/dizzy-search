<?php
	namespace App\Services\Search;
	
	use App\Entities\Cache\EntityCacheIndex;
	use App\Entities\Cache\EntityCache;
	use App\Entities\Search\SearchCache;
	use App\Entities\Search\SearchQuery;
	use App\Storage\Cache\FileCacheStorage;
	use App\Storage\Cache\ICacheStorage;
	use App\Storage\Search\FileQueryCacheStorage;
	use App\Storage\Search\IQueryCacheStorage;

    /** This class contains all the methods related to search cache */
    class SearchService {
        /** @var int Lifetime of a cached search query */
        protected const QUERY_CACHE_LIFETIME = 86400;

        /** @var int Maximum search results */
        protected const MAX_RESULTS = 50;

        /** @var int Number of words before and after match in preview */
        protected const NUM_SEL_WORDS = 16;

        /** @var string HTML tag which leads the match */
        protected const OPEN_HTML = '<span class="search-keyword">';

        /** @var string HTML tag which trails the match */
        protected const CLOSE_HTML = '</span>';

        /** @var ICacheStorage Entity cache storage */
        protected ICacheStorage $cacheStorage;

        /** @var IQueryCacheStorage Search query cache storage */
        protected IQueryCacheStorage $queryCacheStorage;

        public function __construct() {
            $this->cacheStorage = new FileCacheStorage();
            $this->queryCacheStorage = new FileQueryCacheStorage();
        }

        /**
         * This function performs search by the built search query
         * @param SearchQuery $query Search query object
         * @return SearchCache
         */
        public function search(SearchQuery $query): SearchCache {
            $queryStr = $query->str();
            $searchCache = $this->queryCacheStorage->get($queryStr);

            if ($searchCache->numResults() > 0) {
                if ($searchCache->time() >= time() - self::QUERY_CACHE_LIFETIME) {
                    return $searchCache;
                }

                $this->queryCacheStorage->delete($queryStr);
            }

            $searchCache->reset($queryStr);
            $cacheIndex = $this->cacheStorage->list();

            if ($cacheIndex->empty()) {
                return new SearchCache();
            }

            $words = $query->words();
            $exWords = $query->wordsExact();
            $noExact = $query->noExact();
            $cacheIndexArr = $cacheIndex->toArray();

            $minMatch = max(intdiv($query->numWords(), 2), 1);

            foreach ($cacheIndexArr as $cacheEntry) {
                $cache = $this->cacheStorage->cache($cacheEntry[EntityCacheIndex::KEY]);
                if ($cache->empty()) {
                    continue;
                }

                $num = 0;
                $isExact = true;
                $len = 0;
                $st = 0;
                $end = 0;

                if (!$noExact) {
                    list ($num, $len, $st, $end) = $this->searchExact($cache, $exWords);
                }

                if (!$num) {
                    list ($num, $len, $st, $end) = $this->searchNonExact($cache, $words);

                    $isExact = false;
                }

                if ($len >= $minMatch) {
                    $searchCache->add(
                        $cacheEntry[EntityCacheIndex::NAME],
                        $cacheEntry[EntityCacheIndex::URL],
                        $cacheEntry[EntityCacheIndex::TYPE],
                        $cacheEntry[EntityCacheIndex::LANG],
                        $cacheEntry[EntityCacheIndex::KEY],
                        $num,
                        $isExact,
                        $len,
                        $st,
                        $end
                    );
                }
            }

            $searchCache->sort();
            $searchCache->slice(self::MAX_RESULTS);
            $this->setSubstr($searchCache);

            $this->queryCacheStorage->put($queryStr, $searchCache);

            return $searchCache;
        }

        /**
         * Performs a strict search in the given cache
         * @param EntityCache $cache Cache to be scanned
         * @param array $words Words to be searches
         * @return ?array
         *     number of results (int),
         *     max result length,
         *     first match start position,
         *     last match end position
         */
        public function searchExact(EntityCache $cache, array $words): ?array {
            $positions = [];

            foreach ($words as $word) {
                $curSet = $cache->get($word);
                if (!$curSet) {
                    return null;
                }

                $positions[] = $curSet;
            }

            $firstStart = 0;
            $firstEnd = 0;
            $numRes = 0;

            $first = $positions[0];
            unset($positions[0]);

            foreach ($first as $end => $null) {
                $start = $end;

                foreach ($positions as $pos) {
                    if (!array_key_exists(++$end, $pos)) {
                        continue 2;
                    }
                }

                if ($numRes == 0) {
                    $firstStart = $start;
                    $firstEnd = $end;
                }

                $numRes++;
            }

            return [ $numRes, $firstEnd - $firstStart + 1, $firstStart, $firstEnd ];
        }

        /**
         * Performs a non-strict search in the given cache
         * @param EntityCache $cache Cache to be scanned
         * @param array $words Words to be searches
         * @return ?array
         *     number of results (int),
         *     max result length,
         *     first match start position,
         *     last match end position
         */
        public function searchNonExact(EntityCache $cache, array $words): ?array {
            $positions = [];
            foreach ($words as $word) {
                $positions += ($cache->get($word) ?? []);
            }

            if (!$positions) {
                return null;
            }

            krsort($positions);

            $bestLen = 0;
            $bestStart = 0;
            $bestEnd = 0;
            $numRes = 0;

            foreach ($positions as $end => $beforeStart) {
                $start = $end;
                $len = 1;

                unset($positions[$end]);

                while (array_key_exists($beforeStart, $positions)) {
                    $start = $beforeStart;
                    $beforeStart = $positions[$beforeStart];
                    $len++;

                    unset($positions[$start]);
                }

                if ($len > $bestLen) {
                    $bestLen = $len;
                    $bestStart = $start;
                    $bestEnd = $end;
                    $numRes = 1;
                } else if ($len == $bestLen) {
                    $numRes++;
                    $bestStart = $start;
                    $bestEnd = $end;
                }
            }

            return [ $numRes, $bestLen, $bestStart, $bestEnd ];
        }

        /**
         * Generates preview string for all elements of the search cache
         * @param SearchCache $searchCache Search cache to be processed
         * @return void
         */
        public function setSubstr(SearchCache $searchCache): void {
            $searchCache->each(function (array &$el) {
                $rawCache = $this->cacheStorage->raw($el[SearchCache::KEY]);
                $numWords = $rawCache->num();

                if ($numWords == 0) {
                    return;
                }

                $prefix = '';
                $postfix = '';

                $startIndex = $el[SearchCache::MATCH_START] - self::NUM_SEL_WORDS;
                if ($startIndex < 0) {
                    $startIndex = 0;
                } else {
                    $prefix = '... ';
                }

                $endIndex = $el[SearchCache::MATCH_END] + self::NUM_SEL_WORDS;
                if ($endIndex >= $numWords) {
                    $endIndex = $numWords - 1;
                } else {
                    $postfix = ' ...';
                }

                $start = $rawCache->getStart($startIndex);
                $end = $rawCache->getEnd($endIndex);
                $startMatch = $rawCache->getStart($el[SearchCache::MATCH_START]);
                $endMatch = $rawCache->getEnd($el[SearchCache::MATCH_END]);

                $res = substr($rawCache->getRawText(), $start, $end - $start);
			    $res = substr_replace($res, self::CLOSE_HTML, $endMatch - $start, 0);
			    $res = substr_replace($res, self::OPEN_HTML, $startMatch - $start, 0);

                $el[SearchCache::SUBSTR] = $prefix . $res . $postfix;
            });
        }
    }