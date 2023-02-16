<?php
    namespace App\Helpers;

    use App\Entities\Cache\WordDescr;

    class TokenHelper {
        /** Regex used to match non-ignored words */
        const WORD_REGEX = "/[\w\d]{2,}/u";

        /** Regex used to match word separators */
        const DELIMETER_REGEX = "/[^\w\d]+/u";

        /** Words that should not be ignored */
        const ACRONYMS = [ 'IT' ];

        /**
         * @param string $str
         * @param string[] $ignoredWords
         * @return WordDescr[]
         */
        public static function wordDescrs(string $str, $ignoredWords) {
            $baseRes = [];
            preg_match_all(self::WORD_REGEX, $str, $baseRes, PREG_OFFSET_CAPTURE);

            if (!$baseRes) {
                return [];
            }

            return array_map(function ($value) use ($ignoredWords) {
                list ($word, $offset) = $value;
                $isIgnored = false;

                $lcWord = mb_strtolower($word);

                if (!in_array($word, self::ACRONYMS) && in_array($lcWord, $ignoredWords)) {
                    $isIgnored = true;
                }

                return new WordDescr(
                    $lcWord,
                    $offset,
                    $isIgnored
                );
            }, $baseRes[0]);
        }

        /**
         * @param string $str
         * @param string[] $ignoredWords
         * @return string[]
         */
        public static function words(string $str, $ignoredWords) {
            $baseRes = [];
            preg_match_all(self::WORD_REGEX, $str, $baseRes);

            if (!$baseRes) {
                return [];
            }

            return array_filter(array_map(function ($value) use ($ignoredWords) {
                $lcWord = mb_strtolower($value);

                if (!in_array($value, self::ACRONYMS) && in_array($lcWord, $ignoredWords)) {
                    return null;
                }

                return $lcWord;
            }, $baseRes[0]));
        }

        public static function normalize(string $str): string {
            return trim(preg_replace(self::DELIMETER_REGEX, ' ', mb_strtolower($str)));
        }

        /**
         * @param string $str
         * return string[]
         */
        public static function explode(string $str) {
            return preg_split(self::DELIMETER_REGEX, mb_strtolower($str));
        }

        public static function stripWhitespaces(string $text): string {
            $text = preg_replace("/\s+/u", ' ', preg_replace("/\t+/u", '', $text));

            return trim(mb_convert_encoding($text, 'UTF-8', 'UTF-8'));
        }
    }
?>