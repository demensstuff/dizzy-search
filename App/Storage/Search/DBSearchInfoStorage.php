<?php
	namespace App\Storage\Search;
	
	use App\Core\DB;
    use App\Core\DBException;
    use App\Core\DBInc;
	use App\Entities\Search\TopQueries;

    /** MySQL repository implementation of search meta storage */
	class DBSearchInfoStorage implements ISearchInfoStorage {
        /** @var DB DB handle */
		protected DB $db;
		
		public function __construct() {
			$this->db = DB::instance(DBInc::credFile());
		}

        /**
         * Increments the weight of the given query
         * @param string $str Search query
         * @throws DBException
         */
        public function incQueryWeight(string $str): void {
			$this->db->query(
				'INSERT INTO `search_top_words` (`text`, `weight`) VALUES (:text, 1) ' .
				'ON DUPLICATE KEY UPDATE `weight` = `weight` + 1',
				[ 'text' => $str ]
			);
		}

        /**
         * Returns top search queries by weight
         * @param int $num Number of top search queries
         * @return TopQueries
         * @throws DBException
         */
		public function topQueries(int $num): TopQueries {
			$arr = $this->db->select(
				'SELECT * FROM `search_top_words` ORDER BY `weight` DESC LIMIT :limit',
				[ 'limit' => $num ]
			);

            return new TopQueries($arr ?: null);
		}
	}