<?php
    namespace App\Helpers;

    class IgnoredWordsHelper {
        /** Path to the file with the ignored words */
        const IGNORED_WORDS_PATH = '/../assets/cache/ignored_words.json';

        /**
         * @return string[]
         */
        public static function load() {
            $ignoredWordsPath = $_SERVER['DOCUMENT_ROOT'] . self::IGNORED_WORDS_PATH;

            return json_decode(file_get_contents($ignoredWordsPath), true) ?? [];
        }
    }
?>