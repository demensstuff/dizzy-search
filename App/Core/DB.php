<?php
    namespace App\Core;

    /**
     * Wrapper for exceptions
     */
    class DBException extends \Exception{}

    /**
     * Queries to MySQL database
     */
    class DB {
        protected static ?self $db = null;

        // The database connection
        protected \PDO $dbconn;

        /**
         * Creates a PDO instance representing a connection to a database
         * @param string    $pathToFile   path to json with DB-credentials
         */
        public function __construct($pathToFile) {
            if (self::$db !== null) {
                return self::$db;
            }

            $json = json_decode(file_get_contents($pathToFile), true);
            if (!$json) {
                echo '<h1>Cannot parse DB credentials file!</h1>';
                die();
            }

            try {
                $this->dbconn = new \PDO('mysql:dbname=' . $json['db'] .  ';host=' . $json['host'] . ';port=' . $json['port'], $json['user'], $json['pass']);
            } catch (\PDOException $e) {
                echo '<h1>Cannot connect to the database!</h1>';
                die();
            }

            $this->dbconn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->dbconn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

            self::$db = $this;
        }

        /**
         * @param string    $query  Valid MySQL statement
         * @param array    $dba    [optional] This array holds one or more key=>value pairs to set
         * attribute values for the $query
         * @return string   sanitized $query
         */
        private function prepare($query, $dba) {
            try {
                if ($dba) {
                    $stmt = $this->dbconn->prepare($query);
                    $stmt->execute($dba);
                } else {
                    $stmt = $this->dbconn->query($query);
                }
            } catch (PDOException $e) {
                throw new DBException($e->getMessage());
            }

            return $stmt;
        }

        /**
         * @param string    $query  Valid MySQL statement
         * @param array    $dba    [optional] This array holds one or more key=>value pairs to set
         * attribute values for the $query
         * @return array|false    An array containing all of the remaining rows in the result set.
         * An empty array is returned if there are zero results to fetch, or false on failure.
         */
        public function select($query, $dba = null) {
            return $this->prepare($query, $dba)->fetchAll(\PDO::FETCH_ASSOC);
        }

        /**
         * @param string    $query  Valid MySQL statement
         * @param array $dba    [optional] This array holds one or more key=>value pairs to set
         * attribute values for the $query
         * @return mixed|false  Selected database row
         */
        public function fetch($query, $dba = null) {
            return $this->prepare($query, $dba)->fetch(\PDO::FETCH_ASSOC);
        }

        /**
         * @param string    $query  Valid MySQL statement
         * @param array $dba    [optional] This array holds one or more key=>value pairs to set
         * attribute values for the $query
         * @return string|false  The row ID of the last row that was inserted into
         * the database.
         */
        public function query($query, $dba = null) {
            $this->prepare($query, $dba);

            return $this->dbconn->lastInsertID();
        }
    }
?>