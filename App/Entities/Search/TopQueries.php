<?php
	namespace App\Entities\Search;
	
	class TopQueries {
        /** @var string Array key 'text': the query itself */
        public const TEXT = 'text';

        /** @var string Array key 'weight': how many times the query was applied */
        public const WEIGHT = 'weight';

        /** @var array Top queries container */
		public array $arr = [];
		
		public function __construct(?array $arr = null) {
			$this->arr = $arr ?? [];
		}
		
		/**
		 * @return array Top queries as an array
		 */
		public function toArray(): array {
			return $this->arr;
		}
	}