<?php
	namespace App\Storage\Cache;
	
	use App\Entities\Cache\EntityCacheIndex;
	use App\Entities\Cache\EntityCache;
    use App\Entities\Cache\EntityRawCache;

    interface ICacheStorage {
		public function list(): EntityCacheIndex;

		public function cache(string $key): EntityCache;
		
		public function raw(string $key): EntityRawCache;

		public function putList(EntityCacheIndex $index): void;

		public function putCache(string $key, EntityCache $cache): void;
		
		public function putRaw(string $key, EntityRawCache $rawCache): void;
		
		public function remove(string $key): void;
	}