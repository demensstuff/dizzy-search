<?php
    namespace App\Storage\Search;

    use App\Entities\Search\SearchCache;
    use App\Entities\Search\SearchResult;
    use App\Storage\Search\IQueryCacheStorage;

    class FileQueryCacheStorage implements IQueryCacheStorage {
        protected string $cacheDir;

        public function __construct() {
            $this->cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/../.cache/queries/';

            if (!is_dir($this->cacheDir)) {
                mkdir($this->cacheDir, 0777, true);
            }
        }

        /**
         * @var string $str
         * @var SearchResult[] $results
         */
        public function put(string $str, $results) {
            $cache = new SearchCache($str, time(), $results);

            file_put_contents($this->path($str), $cache->toJSON());
        }

        public function get(string $str): ?SearchCache {
            $arr = json_decode(@file_get_contents($this->path($str)), true);
            if (!$arr) {
                return null;
            }

            return new SearchCache(
                $arr['str'],
                $arr['time'],
                SearchResult::manyFromArray($arr['results'])
            );
        }

        public function delete(string $str) {
            $path = $this->path($str);

            if (file_exists($path)) {
                unlink($path);
            }
        }

        protected function path(string $str): string {
            return $this->cacheDir . md5($str) . '.json';
        }
    }
?>