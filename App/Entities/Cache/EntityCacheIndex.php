<?php
	namespace App\Entities\Cache;

    /** This class represents the cache index structure */
	class EntityCacheIndex {
        /** @var string Array key 'name': file/page name */
        public const NAME = 'name';

        /** @var string Array key 'url': relative URL address to page/file */
        public const URL = 'url';

        /** @var string Array key 'type': TYPE_PAGE or TYPE_FILE */
        public const TYPE = 'type';

        /** @var string Array key 'lang': language of the entity */
        public const LANG = 'lang';

        /** @var string Array key 'key': unique hash based on filepath/page URL */
        public const KEY = 'key';

        /** @var string Array key 'mod_marker': file mod time or page content hash */
        public const MOD_MARKER = 'mod_marker';

        /** @var string Array key 'processed_at': timestamp of the last cache attempt */
        public const PROCESSED_AT = 'processed_at';

        /** @var string Cache item 'page' type */
        public const TYPE_PAGE = 'page';

        /** @var string Cache item 'file' type */
        public const TYPE_FILE = 'file';

        /** @var array Cache index container */
        protected array $arr = [];

        public function __construct(?array $list = null) {
            $this->arr = $list ?? [];
        }

        /**
         * Adds data to the index
         * @param string $name File/page name
         * @param string $url Relative URL address to page/file
         * @param string $type TYPE_PAGE or TYPE_FILE
         * @param string $lang Language of the entity
         * @param string $key Unique hash based on filepath/page URL
         * @param mixed $modMarker File mod time or page content hash
         * @param int $processedAt Timestamp of the last cache attempt
         * @return void
         */
		public function add(
			string $name,
			string $url,
			string $type,
			string $lang,
			string $key,
			mixed $modMarker,
			int $processedAt
		): void {
            $this->arr[$key] = [
                self::NAME         => $name,
                self::URL          => $url,
                self::TYPE         => $type,
                self::LANG         => $lang,
                self::KEY          => $key,
                self::MOD_MARKER   => $modMarker,
                self::PROCESSED_AT => $processedAt,
            ];
		}

        /**
         * Removes entry from the index
         * @param string $key Unique hash based on filepath/page URL
         * @return void
         */
        public function remove(string $key): void {
            unset($this->arr[$key]);
        }

        /**
         * @return array Index as an array
         */
        public function toArray(): array {
            return $this->arr;
        }

        /**
         * @param string $key Unique hash based on filepath/page URL
         * @return bool Whether the entry exists
         */
        public function exists(string $key): bool {
            return array_key_exists($key, $this->arr);
        }

        /**
         * @param string $key Unique hash based on filepath/page URL
         * @return array Single element reference
         */
        public function &get(string $key): array {
            return $this->arr[$key];
        }

        /**
         * @return bool Whether the index is empty
         */
        public function empty(): bool {
            return empty($this->arr);
        }
    }