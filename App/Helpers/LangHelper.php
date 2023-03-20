<?php
	namespace App\Helpers;

    /** This class provides a set of methods related to the site languages */
	class LangHelper {
		/** @var string No specified language */
		public const NONE = 'none';
		
		/** @var string English */
		public const EN = 'en';
		
		/** @var string Russian */
		public const RU = 'ru';

        /** @var string[] All available languages */
        public const LANGUAGES = [ self::EN, self::RU ];
		
		/** @var array[string]null Filepath tokens which may mark the language */
		protected const MARKERS = [
            self::EN => [
                'en'       => null,
                'eng'      => null,
                'english'  => null,
            ],
            self::RU => [
                'ru'      => null,
                'rus'     => null,
                'russian' => null,
            ]
		];

        /**
         * @return string Current language
         */
		public static function lang(): string {
			@session_start();
			
			return $_SESSION['language'] ?? self::NONE;
		}

        /**
         * This function tries to assume the language of the file based on the keywords
         * in its URL
         * @param string $url File URL (relative to the document root)
         * @return string Assumed language of the file
         */
		public static function assumeFileLanguage(string $url): string {
			$tokens = TokenHelper::explode($url);
			
			$assumedLang = self::NONE;

            foreach ($tokens as $token) {
                foreach (self::MARKERS as $lang => $markers) {
                    if (!array_key_exists($token, $markers)) {
                        continue;
                    }

                    if ($assumedLang != self::NONE) {
                        return self::NONE;
                    }

                    $assumedLang = $lang;

                    break;
                }
            }
			
			return $assumedLang;
		}
	}