<?php
    namespace App\Storage\Cache;

    use App\Core\DB;
    use App\Core\DBInc;
    use App\Entities\JSONer;

    class DBCacheInfoStorage implements ICacheInfoStorage {
        const INCLUDED_DIRS_KEY = 'included_dirs';

        const EXCLUDED_DIRS_KEY = 'excluded_dirs';

        const EXCLUDED_PAGES_KEY = 'excluded_pages';

        protected DB $db;

        public function __construct() {
            $this->db = new DB(DBInc::credFile());
        }

        /**
         * @return string[]
         */
        public function includedDirs() {
            return $this->settings(self::INCLUDED_DIRS_KEY);
        }

        /**
         * @return string[]
         */
        public function excludedDirs() {
            return $this->settings(self::EXCLUDED_DIRS_KEY);
        }

        /**
         * @return string[]
         */
        public function excludedPages() {
            return $this->settings(self::EXCLUDED_PAGES_KEY);
        }

        /**
         * @param string[] $includedDirs
         */
        public function putIncludedDirs($includedDirs) {
            return $this->putSettings(self::INCLUDED_DIRS_KEY, $includedDirs);
        }

        /**
         * @param string[] $excludedDirs
         */
        public function putExcludedDirs($excludedDirs) {
            return $this->putSettings(self::EXCLUDED_DIRS_KEY, $excludedDirs);
        }

        /**
         * @param string[] $excludedPages
         */
        public function putExcludedPages($excludedPages) {
            return $this->putSettings(self::EXCLUDED_PAGES_KEY, $excludedPages);
        }

        /**
         * @param string $key
         * @return string[]
         */
        protected function settings(string $key) {
            $value = $this->db->fetch(
                'SELECT `value` FROM `search_settings` WHERE `key`=:key',
                [ 'key' => $key ]
            );

            if (!$value) {
                return [];
            }

            $value = json_decode($value['value'], true);
            if (!$value) {
                return [];
            }

            return $value;
        }

        /**
         * @param string $key
         * @param string[] $value
         */
        protected function putSettings(string $key, $value) {
            $value = JSONer::anyToJSON($value);

            $this->db->query(
                'INSERT INTO `search_settings` (`key`, `value`) VALUES (:key, :value)' .
                'ON DUPLICATE KEY UPDATE `value` = :value2',
                [ 'key' => $key, 'value' => $value, 'value2' => $value ]
            );
        }
    }
?>