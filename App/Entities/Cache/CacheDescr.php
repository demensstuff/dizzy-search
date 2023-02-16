<?php
    namespace App\Entities\Cache;

    use App\Entities\JSONer;

    class CacheDescr extends JSONer {
        const TYPE_PAGE = 'page';

        const TYPE_FILE = 'file';

        public string $name;

        public string $url;

        public string $type;

        public string $lang;

        public string $key;

        public mixed $modMarker;

        public int $processedAt;

        public function __construct(
            string $name,
            string $url,
            string $type,
            string $lang,
            string $key,
            mixed $modMarker,
            int $processedAt
        ) {
            $this->name        = $name;
            $this->url         = $url;
            $this->type        = $type;
            $this->lang        = $lang;
            $this->key         = $key;
            $this->modMarker   = $modMarker;
            $this->processedAt = $processedAt;
        }

        /**
         * @param array $arr
         * @return array 'string' => 'CacheDescr'
         */
        public static function map($arr) {
            return array_map(function ($value) {
                return new self(
                    $value['name'],
                    $value['url'],
                    $value['type'],
                    $value['lang'],
                    $value['key'],
                    $value['mod_marker'],
                    $value['processed_at']
                );
            }, $arr);
        }

        /**
         * return array
         */
        public function toArray() {
            return [
                'name'         => $this->name,
                'url'          => $this->url,
                'type'         => $this->type,
                'lang'         => $this->lang,
                'key'          => $this->key,
                'mod_marker'   => $this->modMarker,
                'processed_at' => $this->processedAt
            ];
        }
    }
?>