<?php
	namespace App\Responses;

    /** This class represents a Cache response */
	class CacheResponse extends Response {
        /** @var int Number of added cache entries */
		public int $added;

        /** @var int Number of removed cache entries */
		public int $removed;

        /** @var int Number of unchanged cache entries */
		public int $unchanged;

        /** @var float Caching time */
		public float $time;
		
		/**
         * Converts the object to the array
		 * @return array string $key => int $value
		 */
		public function toArray(): array {
			$arr = parent::toArray();
			
			$arr['added']     = $this->added;
			$arr['removed']   = $this->removed;
			$arr['unchanged'] = $this->unchanged;
			$arr['time']      = $this->time;

			return $arr;
		}
		
		/**
         * Returns a successful response
		 * @param int $added
		 * @param int $removed
		 * @param int $unchanged
		 * @param float $time
		 * @return CacheResponse
		 */
		public static function ok(
			int $added     = 0,
			int $removed   = 0,
			int $unchanged = 0,
			float $time    = 0
		): static {
			$r = parent::ok();
			
			$r->added     = $added;
			$r->removed   = $removed;
			$r->unchanged = $unchanged;
			$r->time      = $time;

			return $r;
		}
	}