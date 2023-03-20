<?php
	namespace App\Entities\Search;
	
	use App\Helpers\IgnoredWordsHelper;
    use App\Helpers\TokenHelper;

    /** This class represents a search query */
	class SearchQuery {
		/** @var string Regex used to check if the string is quoted */
		protected const QUOTED_REGEX = "/^ *\".*\" *$/u";

        /** @var string Raw query string */
		protected string $str;
		
		/** @var string[] Non-ignored search query words */
        protected array $words = [];

        /** @var int Number of non-ignored search query words */
        protected int $numWords = 0;

		/** @var string[] All search query words */
        protected array $wordsExact = [];

        /**
         * @var int Number of all search query words
         */
        protected int $numWordsExact = 0;

        /** @var bool Whether the search query is not exact and consists of ignored
         * words only
         */
        protected bool $isUnspecified = false;

        /** @var bool True if only exact matches should be looked for */
        protected bool $noExact = false;

        /** @var bool True if only exact matches should be looked for */
        protected bool $onlyExact = false;

		public function __construct(string $str, $ignoredWords) {
			$this->str = TokenHelper::normalize($str);
			if (empty($this->str)) {
				return;
			}

            $upStr = mb_strtoupper($str);
			$baseRes = TokenHelper::wordsWithOffsets($upStr);

            if (!$baseRes) {
                $this->isUnspecified = true;

                return;
            }

            foreach ($baseRes as $entry) {
                $lcWord = mb_strtolower($entry[0]);
                $ucWord = mb_strtoupper($entry[0]);

                $this->wordsExact[] = $lcWord;
                $this->numWordsExact++;

                $isIgnored = array_key_exists($lcWord, $ignoredWords);
                $isAcronym = array_key_exists($ucWord, IgnoredWordsHelper::ACRONYMS);

                if ($isIgnored && !$isAcronym) {
                    continue;
                }

                $this->words[] = $isIgnored ? $ucWord : $lcWord;
                $this->numWords++;
            }

			$quoted = preg_match(self::QUOTED_REGEX, $str);
			
			if ($this->numWords == 0 && !$quoted) {
				$this->isUnspecified = true;
				
				return;
			}

            $this->noExact = $this->numWordsExact == 1;
            $this->onlyExact = !$this->noExact && $quoted;
		}

        /**
         * @return string Cleaned query string
         */
        public function str(): string {
            return $this->str;
        }

        /**
         * @return bool Whether the search query is not exact and consists of ignored
         * words only
         */
        public function isUnspecified(): bool {
            return $this->isUnspecified;
        }

        /**
         * @return int Number of non-ignored search query words
         */
        public function numWords(): int {
            return $this->numWords;
        }

        /**
         * @return int Number of all words in the query
         */
        public function numWordsExact(): int {
            return $this->numWordsExact;
        }

        /**
         * @return bool Whether only exact matches should be looked for
         */
        public function onlyExact(): bool {
            return $this->onlyExact;
        }

        /**
         * @return bool True if only exact matches should be looked for
         */
        public function noExact(): bool {
            return $this->noExact;
        }

        /**
         * @return string[] All search query words
         */
        public function wordsExact(): array {
            return $this->wordsExact;
        }

        /**
         * @return string[] Non-ignored search query words
         */
        public function words(): array {
            return $this->words;
        }
	}