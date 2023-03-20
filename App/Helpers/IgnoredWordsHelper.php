<?php
	namespace App\Helpers;

    /** This class provides a method to load ignored words */
	class IgnoredWordsHelper {
        /** Words that should not be ignored */
        public const ACRONYMS = [
            'IT' => null
        ];

		/** @var string Path to the file with the ignored words */
		protected const IGNORED_WORDS_PATH = '/../assets/cache/ignored_words.json';
		
		/**
         * Returns a hashmap of all ignored words (string word => null)
		 * @return string[]
		 */
		public static function load(): array {
			$ignoredWordsPath = $_SERVER['DOCUMENT_ROOT'] . self::IGNORED_WORDS_PATH;
			
			$res = [];
			$raw = json_decode(file_get_contents($ignoredWordsPath), true) ?? [];
			
			foreach ($raw as $word) {
				$res[$word] = null;
			}
			
			return $res;
		}
	}