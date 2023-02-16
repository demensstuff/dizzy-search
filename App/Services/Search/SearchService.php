<?php
    namespace App\Services\Search;

    use App\Entities\Cache\CacheDescr;
    use App\Entities\Cache\WordOffsets;
    use App\Entities\Search\SearchCache;
    use App\Entities\Search\SearchQuery;
    use App\Entities\Search\SearchResult;
    use App\Helpers\LangHelper;
    use App\Storage\Cache\FileCacheStorage;
    use App\Storage\Cache\ICacheStorage;
    use App\Storage\Search\FileQueryCacheStorage;
    use App\Storage\Search\IQueryCacheStorage;

    class SearchService {
        /** Lifetime of a cached search query */
        const QUERY_CACHE_LIFETIME = 86400;

        /** Maximum search results */
        const MAX_RESULTS = 50;

        /** Number of words before and after match in preview */
        const NUM_SEL_WORDS = 16;

        /** HTML tag which leads the match */
        const OPEN_HTML = '<span class="search-keyword">';

        /** HTML tag which trails the match */
        const CLOSE_HTML = '</span>';

        protected ICacheStorage $cacheStorage;

        protected IQueryCacheStorage $queryCacheStorage;

        public function __construct() {
            $this->cacheStorage = new FileCacheStorage();
            $this->queryCacheStorage = new FileQueryCacheStorage();
        }

        /**
         * @param SearchQuery $query
         * @return SearchResult[]
         */
        public function search(SearchQuery $query) {
            /** @var SearchResult[] $results */
            $results = $this->cachedSearchResults($query->str);

            if ($results !== null) {
                return $results;
            }

            /** @var array $listDescr 'string' => 'CacheDescr' */
            $listDescr = $this->cacheStorage->list();
            if (!$listDescr) {
                return [];
            }

            $results = array_filter(array_map(function ($descr) use ($query) {
                $offsets = $this->cacheStorage->offsets($descr->key);
                if (!$offsets) {
                    return null;
                }

                $numOffsets = count($offsets);

                /** @var SearchResult $result */
                if (!$query->noExact) {
                    $result = $this->searchExact($query, $descr, $offsets, $numOffsets);
                    if ($result) {
                        return $result;
                    }
                }

                return $this->searchTokenized($query, $descr, $offsets, $numOffsets);
            }, $listDescr));

            self::sortResults($results);
            $results = array_slice($results, 0, self::MAX_RESULTS);

            $this->queryCacheStorage->put($query->str, $results);

            return $results;
        }

        /**
         * @param SearchResult[] &$pResults
         * @param string|null $lang
         */
        public static function sortResults(&$pResults, string $lang = null) {
            usort($pResults, function ($a, $b) use ($lang) {
                if ($a->isExact != $b->isExact) {
                    return $b->isExact <=> $a->isExact;
                }

                if ($lang) {
                    $aHasRightLang = $a->lang == $lang || $a->lang == LangHelper::NONE;
                    $bHasRightLang = $b->lang == $lang || $b->lang == LangHelper::NONE;

                    if ($aHasRightLang != $bHasRightLang) {
                        if ($bHasRightLang) {
                            return 1;
                        }

                        return -1;
                    }
                }

                if ($a->type != $b->type) {
                    if ($b->type == CacheDescr::TYPE_PAGE) {
                        return 1;
                    }

                    return -1;
                }

                if ($a->longestMatch != $b->longestMatch) {
                    return $b->longestMatch <=> $a->longestMatch;
                }

                return $b->numMatches <=> $a->numMatches;
            });
        }

        /**
         * @param SearchQuery $query
         * @param CacheDescr $descr
         * @param WordOffsets[] $offsets
         * @param int $numOffsets
         * @return SearchResult|null
         */
        protected function searchExact(
            SearchQuery $query,
            CacheDescr $descr,
            $offsets,
            int $numOffsets
        ): ?SearchResult {
            /** @var string|null $exact */
            $exact = $this->cacheStorage->exact($descr->key);

            if ($exact === null) {
                return null;
            }

            $pos = strpos($exact, $query->str);
            if ($pos === false) {
                return null;
            }

            $startMatchIndex = array_search($pos, array_column($offsets, 'exactPos'));
            if ($startMatchIndex === null) {
                return null;
            }

            $endMatchIndex = $startMatchIndex + $query->numWordsStr - 1;
            if ($endMatchIndex >= $numOffsets) {
                $endMatchIndex = $numOffsets - 1;
            }

            $numMatches = 1;
            while (($pos = strpos($exact, $query->str, $pos + 1)) !== false) {
                $numMatches++;
            }

            return $this->buildResult(
                $descr,
                $offsets,
                $numOffsets,
                $startMatchIndex,
                $endMatchIndex,
                $numMatches
            );
        }

        /**
         * @param SearchQuery $query
         * @param CacheDescr $descr
         * @param WordOffsets[] $offsets
         * @param int $numOffsets
         * @return SearchResult|null
         */
        protected function searchTokenized(
            SearchQuery $query,
            CacheDescr $descr,
            $offsets,
            int $numOffsets
        ): ?SearchResult {
            $tokenized = $this->cacheStorage->tokenized($descr->key);
            if ($tokenized === null) {
                return null;
            }

            list ($numMatches, $longestMatch, $startPos, $endPos) = self::tokenizedResults(
                $tokenized,
                $query
            );

            if ($numMatches === null) {
                return null;
            }

            $startMatchIndex = array_search($startPos, array_column(
                $offsets,
                'tokenizedPos'
            ));

            $endMatchIndex = array_search($endPos, array_column($offsets, 'tokenizedPos'));

            if ($startMatchIndex === null || $endMatchIndex === null) {
                return null;
            }

            return $this->buildResult(
                $descr,
                $offsets,
                $numOffsets,
                $startMatchIndex,
                $endMatchIndex,
                $numMatches,
                false,
                $longestMatch
            );
        }

        /**
         * @param CacheDescr $descr
         * @param WordOffsets[] $offsets
         * @param int $numOffsets
         * @param int $startMatchIndex
         * @param int $endMatchIndex
         * @param int $numMatches
         * @param bool $isExact
         * @param int $longestMatch
         * @return SearchResult
         */
        protected function buildResult(
            CacheDescr $descr,
            $offsets,
            int $numOffsets,
            int $startMatchIndex,
            int $endMatchIndex,
            int $numMatches,
            bool $isExact = true,
            int $longestMatch = 0
        ): SearchResult {
            $prefix = '';
            $postfix = '';

            $startIndex = $startMatchIndex - self::NUM_SEL_WORDS;
            if ($startIndex < 0) {
                $startIndex = 0;
            } else {
                $prefix = '... ';
            }

            $endIndex = $endMatchIndex + self::NUM_SEL_WORDS;
            if ($endIndex >= $numOffsets) {
                $endIndex = $numOffsets - 1;
            } else {
                $postfix = ' ...';
            }

            $raw = $this->cacheStorage->raw($descr->key);

            $start = $offsets[$startIndex]->start;
            $end = $offsets[$endIndex]->end;
            $startMatch = $offsets[$startMatchIndex]->start;
            $endMatch = $offsets[$endMatchIndex]->end;

            $substr = substr($raw, $start, $end - $start);
            $substr = substr_replace($substr, self::CLOSE_HTML, $endMatch - $start, 0);
            $substr = substr_replace($substr, self::OPEN_HTML, $startMatch - $start, 0);

            return new SearchResult(
                $descr->name,
                $descr->url,
                $descr->type,
                $descr->lang,
                $descr->key,
                $numMatches,
                $isExact,
                $longestMatch,
                $prefix . $substr . $postfix
            );
        }

        /**
         * @param string $str
         * @return SearchResult[]|null
         */
        protected function cachedSearchResults(string $str) {
            /** @var SearchCache $cachedQuery */
            $cachedQuery = $this->queryCacheStorage->get($str);
            if ($cachedQuery === null) {
                return null;
            }

            $cacheValidAfter = time() - self::QUERY_CACHE_LIFETIME;

            if ($cachedQuery && $cachedQuery->time >= $cacheValidAfter) {
                return $cachedQuery->results;
            }

            $this->queryCacheStorage->remove($str);

            return null;
        }

        /**
         * @param string[] $c
         * @param SearchQuery $q
         * @return int[]
         */
        protected static function tokenizedResults($tokenized, SearchQuery $query) {
            $numMatches = null;
            $longestMatch = intdiv($query->numWords, 2) - 1;
            $startPos = 0;
            $endPos = 0;

            $numTokenized = count($tokenized);
            $startSel = null;

            for ($i = 0; $i <= $numTokenized; $i++) {
                if ($i < $numTokenized && in_array($tokenized[$i], $query->words)) {
                    if ($startSel === null) {
                        $startSel = $i;
                    }

                    continue;
                }

                if ($startSel === null) {
                    continue;
                }

                $lenSel = $i - $startSel;

                if ($lenSel > $longestMatch) {
                    $longestMatch = $lenSel;
                    $startPos = $startSel;
                    $endPos = $i - 1;
                    $numMatches = 1;
                } else if ($lenSel == $longestMatch && $numMatches !== null) {
                    $numMatches++;
                }

                $startSel = null;
            }

            return [ $numMatches, $longestMatch, $startPos, $endPos ];
        }
    }
?>