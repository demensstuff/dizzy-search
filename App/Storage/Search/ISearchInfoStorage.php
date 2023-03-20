<?php
	namespace App\Storage\Search;
	
	use App\Entities\Search\TopQueries;
	
	interface ISearchInfoStorage {
		public function incQueryWeight(string $str);

		public function topQueries(int $num): TopQueries;
	}