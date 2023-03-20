<?php
	namespace App\Responses;

    /** This class represents a search response */
	class SearchResponse extends Response {
		/** @var ?array $results Search cache results */
		public ?array $results;

        /** @var int Page number */
		public int $page;

        /** @var int Max page */
		public int $maxPage;

        /** @var int First result index */
		public int $firstIndex;

        /** @var int Last result index */
		public int $lastIndex;
		
		/**
		 * @return array Array representation of the response
		 */
		public function toArray(): array {
			$arr = parent::toArray();
			
			$arr['results']     = $this->results;
			$arr['page']        = $this->page;
			$arr['maxPage']     = $this->maxPage;
			$arr['first_index'] = $this->firstIndex;
			$arr['last_index']  = $this->lastIndex;
			
			return $arr;
		}
		
		/**
         * Creates a successful response
		 * @param ?array $results Search cache results
		 * @param int $page Current page
		 * @param int $maxPage Max page
		 * @param int $firstIndex First result index
		 * @param int $lastIndex Last result index
		 * @return SearchResponse
		 */
		public static function ok(
			array $results  = null,
			int $page       = 0,
			int $maxPage    = 0,
			int $firstIndex = 0,
			int $lastIndex  = 0
		): static {
			$r = parent::ok();
			
			$r->results    = $results;
			$r->page       = $page;
			$r->maxPage    = $maxPage;
			$r->firstIndex = $firstIndex;
			$r->lastIndex  = $lastIndex;
			
			return $r;
		}
	}