<?php
	namespace App\Entities\Cache;

    /** This class represents the main cache file.
     * The keys of the $arr are words (string tokens). The values are the arrays of the
     * following structure: keys are the number of the words in the raw cache; values
     * are the number of the previous non-ignored word or null.
     * Example of cache: 'Hello, hello in the world.'
     * Example of $arr:
     *     'hello' => [ 0 => null, 1 => 0 ],
     *     'in'    => [ 2 => 1 ],
     *     'the'   => [ 3 => 1 ],
     *     'world' => [ 4 => 1 ]
     */
    class EntityCache {
        /** @var array Value container */
        protected array $arr = [];

        public function __construct(?array $arr = null) {
            $this->arr = $arr ?? [];
        }

        /**
         * Adds entry to the cache
         * @param string $word Word to be saved
         * @param int $pos Word number
         * @param ?int $prevNonIgnoredPos Number of the last non-ignored word
         * @return void
         */
        public function add(string $word, int $pos, ?int $prevNonIgnoredPos): void {
            if (!array_key_exists($word, $this->arr)) {
                $this->arr[$word] = [];
            }

            $this->arr[$word][$pos] = $prevNonIgnoredPos;
        }

        /**
         * @return array Cache as an array
         */
        public function toArray(): array {
            return $this->arr;
        }

        /**
         * @return bool Whether the cache is empty
         */
        public function empty(): bool {
            return empty($this->arr);
        }

        /**
         * Returns the given word positions
         * @param string $word Word to search
         * @return ?array [ int $position => int $prevNonIgnoredPosition ]
         */
        public function get(string $word): ?array {
            return $this->arr[$word] ?? null;
        }
	}