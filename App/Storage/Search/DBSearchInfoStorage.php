<?php
    namespace App\Storage\Search;

    use App\Core\DB;
    use App\Core\DBInc;
    use App\Entities\Search\TopQuery;
    use App\Storage\Search\ISearchInfoStorage;

    class DBSearchInfoStorage implements ISearchInfoStorage {
        protected DB $db;

        public function __construct() {
            $this->db = new DB(DBInc::credFile());
        }

        public function incQueryWeight(string $str) {
            $this->db->query(
                'INSERT INTO `search_top_words` (`text`, `weight`) VALUES (:text, 1) ' .
                'ON DUPLICATE KEY UPDATE `weight` = `weight` + 1',
                [ 'text' => $str ]
            );
        }

        /**
         * @param int $num
         * @return TopQuery[]
         */
        public function topQueries(int $num) {
            $arr = $this->db->select(
                'SELECT * FROM `search_top_words` ORDER BY `weight` DESC LIMIT :limit',
                [ 'limit' => (int) $num ]
            );

            if (!$arr) {
                return [];
            }

            return TopQuery::manyFromArray($arr);
        }
    }
?>