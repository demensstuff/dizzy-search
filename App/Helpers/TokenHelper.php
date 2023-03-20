<?php
	namespace App\Helpers;

    /** This class provides helper functions to tokenize texts */
	class TokenHelper {
		/** Regex used to match non-ignored words */
		protected const WORD_REGEX = "/[\w+]{2,}/u";
		
		/** Regex used to match word separators */
		const DELIMITER_REGEX = "/\W+/u";

        /** @var array UTF-8 entities which may mark lists */
        protected const UTF8_LIST_MARKERS = [
            "\xef\x82\xa7", // F0A7
            "\xef\x82\xb7", // F0B7
            "\xef\x82\xbc" // F0BC
        ];

        /**
         * Removes all word separators from the string
         * @param string $str String to be normalized
         * @return string Normalized string
         */
		public static function normalize(string $str): string {
			return trim(preg_replace(self::DELIMITER_REGEX, ' ', mb_strtolower($str)));
		}
		
		/**
         * This function splits the string by word separators
		 * @param string $str String to be processed
		 * return string[] Array of tokens
		 */
		public static function explode(string $str): array {
			return preg_split(self::DELIMITER_REGEX, mb_strtolower($str));
		}

        /**
         * Fixes encoding, removes malformed characters and redundant word separators
         * @param string $text String to be cleaned up
         * @return string Cleaned up string
         */
		public static function cleanUp(string $text): string {
            $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);

            $text = preg_replace("/[[:cntrl:]\t]+/u", '', $text);
            $text = preg_replace("/\s+/u", ' ', $text);

            return str_replace(self::UTF8_LIST_MARKERS, '-', $text);
		}

        /**
         * Splits the string into words and returns its offsets
         * @param string $str String to be split
         * @return array [ [ string $word, int $offset ] ]
         */
        public static function wordsWithOffsets(string $str): array {
            $baseRes = [];
            preg_match_all(self::WORD_REGEX, $str, $baseRes, PREG_OFFSET_CAPTURE);

            return $baseRes ? $baseRes[0] : [];
        }
	}