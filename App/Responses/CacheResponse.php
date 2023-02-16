<?php
    namespace App\Responses;

    class CacheResponse extends Response {
        public int $added;
        public int $removed;
        public int $unchanged;

        /**
         * @return array
         */
        public function toArray() {
            $arr = parent::toArray();

            $arr['added']     = $this->added;
            $arr['removed']   = $this->removed;
            $arr['unchanged'] = $this->unchanged;

            return $arr;
        }

        /**
         * @param int $added
         * @param int $removed
         * @param int $unchanged
         * @return CacheResponse
         */
        public static function ok(
            int $added     = 0,
            int $removed   = 0,
            int $unchanged = 0
        ): static {
            $r = parent::ok();

            $r->added     = $added;
            $r->removed   = $removed;
            $r->unchanged = $unchanged;

            return $r;
        }
    }
?>