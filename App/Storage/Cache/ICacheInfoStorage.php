<?php
	namespace App\Storage\Cache;
	
	interface ICacheInfoStorage {
		public function includedDirs(): array;

		public function excludedDirs(): array;

		public function excludedPages(): array;

		public function putIncludedDirs($includedDirs): void;

		public function putExcludedDirs($excludedDirs): void;

		public function putExcludedPages($excludedPages): void;
	}