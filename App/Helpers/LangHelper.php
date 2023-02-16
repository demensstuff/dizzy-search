<?php
    namespace App\Helpers;

    class LangHelper {
        /** No specified language */
        const NONE = 'none';

        /** English */
        const EN = 'en';

        /** Russian */
        const RU = 'ru';

        /** If these words are present in a filepath, assume that the language is English */
        const EN_MARKERS = [ 'en', 'eng', 'english' ];

        /* If these words are present in a filepath, assume that the language is Russian */
        const RU_MARKERS = [ 'ru', 'rus', 'russian' ];

        public static function lang(): string {
            @session_start();

            return $_SESSION['language'] ?? self::NONE;
        }

        public static function assumeFileLanguage(string $url): string {
            /** @var string[] $tokens */
            $tokens = TokenHelper::explode($url);

            $en = false;
            $ru = false;

            foreach ($tokens as $token) {
                if (!$en && in_array($token, self::EN_MARKERS)) {
                    $en = true;

                    if ($ru) {
                        break;
                    }
                }

                if (!$ru && in_array($token, self::RU_MARKERS)) {
                    $ru = true;

                    if ($en) {
                        break;
                    }
                }
            }

            if ($en == $ru) {
                return self::NONE;
            }

            if ($en) {
                return self::EN;
            }

            return self::RU;
        }
    }
?>