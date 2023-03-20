<?php
	namespace App\Storage\Cache;
	
	use App\Core\DB;
    use App\Core\DBException;
    use App\Core\DBInc;
    use App\Helpers\ArrayHelper;

    /** MySQL repository implementation of cache meta storage */
    class DBCacheInfoStorage implements ICacheInfoStorage {
        /** @var string Included directories setting key */
		protected const INCLUDED_DIRS_KEY = 'included_dirs';

        /** @var string Excluded directories setting key */
        protected const EXCLUDED_DIRS_KEY = 'excluded_dirs';

        /** @var string Excluded pages setting key */
        protected const EXCLUDED_PAGES_KEY = 'excluded_pages';

        /** @var DB DB handler */
		protected DB $db;
		
		public function __construct() {
			$this->db = DB::instance(DBInc::credFile());
		}

        /**
         * Retrieves included directories
         * @return string[]
         * @throws DBException
         */
		public function includedDirs(): array {
			return $this->settings(self::INCLUDED_DIRS_KEY);
		}

        /**
         * Retrieves excluded directories
         * @return string[]
         * @throws DBException
         */
		public function excludedDirs(): array {
			return $this->settings(self::EXCLUDED_DIRS_KEY);
		}

        /**
         * Retrieves excluded pages
         * @return string[]
         * @throws DBException
         */
		public function excludedPages(): array {
			return $this->settings(self::EXCLUDED_PAGES_KEY);
		}

        /**
         * Saves included directories
         * @param string[] $includedDirs
         * @throws DBException
         */
		public function putIncludedDirs($includedDirs): void {
			$this->putSettings(self::INCLUDED_DIRS_KEY, $includedDirs);
		}

        /**
         * Saves excluded directories
         * @param string[] $excludedDirs
         * @throws DBException
         */
		public function putExcludedDirs($excludedDirs): void {
			$this->putSettings(self::EXCLUDED_DIRS_KEY, $excludedDirs);
		}

        /**
         * Saves excluded pages
         * @param string[] $excludedPages
         * @throws DBException
         */
		public function putExcludedPages($excludedPages): void {
			$this->putSettings(self::EXCLUDED_PAGES_KEY, $excludedPages);
		}

        /**
         * Retrieves settings from the DB
         * @param string $key Setting key
         * @return string[] Setting value
         * @throws DBException
         */
		protected function settings(string $key): array {
			$value = $this->db->fetch(
				'SELECT `value` FROM `search_settings` WHERE `key`=:key',
				[ 'key' => $key ]
			);
			
			if (!$value) {
				return [];
			}
			
			return json_decode($value['value'], true) ?: [];
		}

        /**
         * Saves settings in the DB
         * @param string $key Setting key
         * @param string[] $value Setting value
         * @throws DBException
         */
		protected function putSettings(string $key, array $value): void {
			$value = ArrayHelper::toJSON($value);
			
			$this->db->query(
				'INSERT INTO `search_settings` (`key`, `value`) VALUES (:key, :value)' .
				'ON DUPLICATE KEY UPDATE `value` = :value2',
				[ 'key' => $key, 'value' => $value, 'value2' => $value ]
			);
		}
	}