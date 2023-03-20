<?php
	namespace App\Core;

    /** This class is used to provide valid DB credentials */
	class DBInc {
        /**
         * @return string Path to file with DB credentials based on server name
         */
		public static function credFile(): string {
            return match ($_SERVER['SERVER_NAME']) {
                default =>
                    $_SERVER['DOCUMENT_ROOT'] . '/../.config/db/db.json',
            };
		}
	}