<?php
    namespace App\Core;

    class DBInc {
        public static function credFile(): string {
            switch ($_SERVER['SERVER_NAME']) {
                return $_SERVER['DOCUMENT_ROOT'] . '/../.config/db/db.json';
            }
        }
    }
?>