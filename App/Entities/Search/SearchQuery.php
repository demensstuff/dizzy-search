<?php
    namespace App\Entities\Search;

    use App\Helpers\TokenHelper;

    class SearchQuery {
        /** Regex used to check if the string is quoted */
        const QUOTED_REGEX = "/^ *\".*\" *$/u";

        public string $str;

        public int $numWordsStr = 0;

        /** @var string[] $words */
        public $words = [];

        public int $numWords = 0;

        public bool $isUnspecified = false;

        public bool $noExact = false;

        public bool $onlyExact = false;

        /*
         * @param string $str
         * @param string[] $ignoredWords
         */
        public function __construct(string $str, $ignoredWords) {
            $this->str = TokenHelper::normalize($str);
            if (empty($this->str)) {
                return;
            }

            $this->words    = TokenHelper::words(mb_strtoupper($str), $ignoredWords);
            $this->numWords = count($this->words);

            $quoted = preg_match(self::QUOTED_REGEX, $str);

            if ($this->numWords == 0 && !$quoted) {
                $this->isUnspecified = true;

                return;
            }

            $this->numWordsStr = mb_substr_count($this->str, ' ') + 1;

            if ($this->numWordsStr == 1) {
                $this->noExact = true;
            } else if ($quoted) {
                $this->onlyExact = true;
            }
        }
    }
?>