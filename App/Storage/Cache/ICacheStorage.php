<?php
    namespace App\Storage\Cache;

    use App\Entities\Cache\CacheDescr;
    use App\Entities\Cache\WordOffsets;

    interface ICacheStorage {
        /**
         * @return CacheDescr[]|null
         */
        public function list();

        /**
         * @return WordOffsets[]|null
         */
        public function offsets(string $key);

        public function exact(string $key): ?string;

        /**
         * @return string[]|null
         */
        public function tokenized(string $key);

        public function raw(string $key): ?string;

        /**
         * @param CacheDescr[] $listDescr
         */
        public function putList($listDescr);

        /**
         * @param string $key
         * @param WordOffsets[]|null $offsets
         */
        public function putOffsets(string $key, $offsets);

        public function putExact(string $key, string $exact);

        /**
         * @param string $key
         * @param string[] $tokenized
         */
        public function putTokenized(string $key, $tokenized);

        public function putRaw(string $key, string $raw);

        public function remove(string $key);
    }
?>