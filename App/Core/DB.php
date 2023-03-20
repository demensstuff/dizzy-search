<?php
	namespace App\Core;
	
	use Exception;
    use PDO;
	use PDOException;
    use PDOStatement;

    /** Wrapper for DB exceptions */
	class DBException extends Exception{}

    /** PDO wrapper */
	class DB {
        /** @var ?self Class instance */
		protected static ?self $db = null;
		
		/** @var PDO Database connection handle */
		protected PDO $dbConn;

        /**
         * @param string $pathToFile Path to json with DB credentials
         * @return self DB instance
         */
        public static function instance(string $pathToFile): static {
            if (self::$db === null) {
                self::$db = new self($pathToFile);
            }

            return self::$db;
        }

		protected function __construct(string $pathToFile) {
			$json = json_decode(file_get_contents($pathToFile), true);
			if (!$json) {
				echo '<h1>Cannot parse DB credentials file!</h1>';
				die();
			}
			
			try {
				$this->dbConn = new PDO($json['type'] . ':dbname=' . $json['name'] .  ';host=' .
                    $json['host'] .  ';port=' . $json['port'], $json['user'], $json['pass']);
			} catch (PDOException) {
				echo '<h1>Cannot connect to the database!</h1>';
				die();
			}
			
			$this->dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->dbConn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		}

        /**
         * @param string $query Valid MySQL statement
         * @param ?array $dba Statement params (key => value)
         * @return ?PDOStatement Sanitized $query
         * @throws DBException
         */
		private function prepare(string $query, array $dba = null): ?PDOStatement {
			try {
				if ($dba) {
					$stmt = $this->dbConn->prepare($query);
					$stmt->execute($dba);
				} else {
					$stmt = $this->dbConn->query($query);
				}
			} catch (PDOException $e) {
				throw new DBException($e->getMessage());
			}
			
			return $stmt ?: null;
		}

        /**
         * @param string $query Valid MySQL statement
         * @param ?array $dba Statement params (key => value)
         * @return ?array Array of arrays of results (column => value)
         * @throws DBException
         */
		public function select(string $query, array $dba = null): ?array {
			$res = $this->prepare($query, $dba)->fetchAll(PDO::FETCH_ASSOC);

            return $res ?: null;
		}

        /**
         * @param string $query Valid MySQL statement
         * @param ?array $dba Statement params (key => value)
         * @return ?array Array of results (column => value)
         * @throws DBException
         */
		public function fetch(string $query, array $dba = null): ?array {
			$res = $this->prepare($query, $dba)->fetch(PDO::FETCH_ASSOC);
            if (!$res) {
                return null;
            }

            return $res;
		}

        /**
         * @param string $query Valid MySQL statement
         * @param ?array $dba Statement params (key => value)
         * @return ?string Last inserted ID (for select statements)
         * @throws DBException
         */
		public function query(string $query, array $dba = null): ?string {
			$res = $this->prepare($query, $dba);
            if (!$res) {
                return null;
            }
			
			$res = $this->dbConn->lastInsertID();

            return $res ?: null;
		}
	}