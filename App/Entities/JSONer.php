<?php
    namespace App\Entities;

    abstract class JSONer {
        /**
         * @return array
         */
        abstract public function toArray();

        public function toJSON(bool $prettify = false): string {
            return self::anyToJSON($this->toArray(), $prettify);
        }

        /** @param JSONer[] $results
         * @return string
         */
        public static function manyToJSON($results, bool $prettify = false): string {
            return self::anyToJSON(self::manyToArray($results), $prettify);
        }

        /**
         * @param mixed $any
         * @param bool $prettify
         */
        public static function anyToJSON($any, bool $prettify = false): string {
            $flags = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE;

            if ($prettify) {
                $flags |= JSON_PRETTY_PRINT;
            }

            return json_encode($any, $flags);
        }

        /**
         * @param JSONer[] $arr
         * @return array
         */
        public static function manyToArray($arr) {
            return array_map(function ($value) {
                return $value->toArray();
            }, $arr);
        }
    }
?>