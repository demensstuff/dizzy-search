<?php
	namespace App\Storage\Search;
	
	use App\Entities\Search\SearchCache;
	
	interface IQueryCacheStorage {
		public function put(string $str, SearchCache $searchCache);
		
		public function get(string $str): SearchCache;
		
		public function delete(string $str);
	}