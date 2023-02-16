<?php
    namespace App\Services\Cache;

    use App\Entities\Cache\CacheDescr;
    use App\Entities\Cache\WordDescr;
    use App\Entities\Cache\WordOffsets;
    use App\Helpers\FilesystemHelper;
    use App\Helpers\LangHelper;
    use App\Helpers\PageHelper;
    use App\Helpers\TextExtractHelper;
    use App\Helpers\TokenHelper;
    use App\Storage\Cache\FileCacheStorage;
    use App\Storage\Cache\ICacheStorage;

    class CacheService {
        /** Page content storage selector */
        const XPATH_CONTENT = "//header"; // 'main';

        /** Allowed Extensions */
        const EXTS = [ 'pdf', 'docx', 'xlsx' ];

        /** Language get param key */
        const LANG_KEY = 'l';

        protected ICacheStorage $cacheStorage;

        protected int $prefixLength;

        protected TextExtractHelper $textExtractHelper;

        /** @var string[] $included */
        protected $included = [];

        /** @var string[] $excluded */
        protected $excluded = [];

        /** @var string[] $checked */
        protected $checked = [];

        /** @var string[] $ignoredWords */
        protected $ignoredWords = [];

        protected string $domain;

        protected string $lang;

        protected \CurlHandle $ch;

        /** @var array $listDescr 'string' => 'CacheDescr' */
        protected $listDescr = [];

        protected int $processedAt;

        protected int $addedCtr = 0;

        protected int $removedCtr = 0;

        protected int $unchangedCtr = 0;

        public function __construct() {
            $this->cacheStorage = new FileCacheStorage();

            $this->prefixLength = mb_strlen($_SERVER['DOCUMENT_ROOT']);
            $this->textExtractHelper = new TextExtractHelper();
        }

        /**
         * @param string[] $ignoredWords
         */
        public function prepareCaching($ignoredWords) {
            $this->ignoredWords = $ignoredWords;
            $this->listDescr = $this->cacheStorage->list() ?? [];
            $this->processedAt = time();
            $this->addedCtr = 0;
            $this->removedCtr = 0;
            $this->unchangedCtr = 0;
        }

        /**
         * @param string[] $included
         * @param string[] $excluded
         */
        public function cacheFiles($included, $excluded) {
            $this->included = array_map(function ($value) {
                return $_SERVER['DOCUMENT_ROOT'] . $value;
            }, $included);

            $this->excluded = array_map(function ($value) {
                return $_SERVER['DOCUMENT_ROOT'] . $value;
            }, $excluded);

            $this->checked = [];

            foreach ($this->included as $dir) {
                $this->cacheFilesInDir($dir);
            }
        }

        /**
         * @param string[] $excluded
         */
        public function cachePages($excluded) {
            libxml_use_internal_errors(true);

            $this->included = [];

            $this->domain = PageHelper::domain();

            $this->ch = curl_init();
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($this->ch, CURLOPT_HEADER, true);

            foreach ([ LangHelper::EN, LangHelper::RU ] as $lang) {
                $this->checked = [];
                $this->excluded = array_map(function ($value) use ($lang) {
                    return PageHelper::setGetParam($value, self::LANG_KEY, $lang);
                }, $excluded);

                $this->lang = $lang;

                $this->cachePageRec('');
            }

            $this->checked = [];

            $this->cachePageRec('');

            libxml_clear_errors();
        }

        /**
         * @return array
         */
        public function finishCaching() {
            foreach ($this->listDescr as $key => $descr) {
                if ($descr->processedAt >= $this->processedAt) {
                    continue;
                }

                $this->cacheStorage->remove($key);
                unset($this->listDescr[$key]);
                $this->removedCtr++;
            }

            $this->cacheStorage->putList($this->listDescr);

            return [ $this->addedCtr, $this->removedCtr, $this->unchangedCtr ];
        }

        protected function cachePageRec(string $url) {
            $url = PageHelper::setGetParam($url, self::LANG_KEY, $this->lang);

            $xpath = $this->getXPathAndRedirectedURL($url);
            if (!$xpath) {
                return;
            }

            $this->processPageLinks($xpath);

            $query = self::XPATH_CONTENT . PageHelper::XPATH_TEXT_ELEMENTS;
            $rawText = PageHelper::rawTextXPath($xpath, $query);

            $modMarker = md5($rawText);
            if (in_array($modMarker, $this->included)) {
                return;
            }

            $this->included[] = $modMarker;

            $key = md5('page:' . $this->lang . ':' . $url);
            $listed = array_key_exists($key, $this->listDescr);

            error_log("processing page: " . $url);

            if ($listed && $this->listDescr[$key]->modMarker == $modMarker) {
                $this->listDescr[$key]->processedAt = $this->processedAt;
                $this->unchangedCtr++;

                return;
            }

            $name = PageHelper::rawTextXPath($xpath, "//title/text()");
            if (!$name) {
                $name = $url;
            }

            $this->buildCaches($key, $rawText);

            $this->listDescr[$key] = new CacheDescr(
                $name,
                $url,
                CacheDescr::TYPE_PAGE,
                $this->lang,
                $key,
                $modMarker,
                $this->processedAt
            );
        }

        protected function getXPathAndRedirectedURL(string &$pUrl): ?\DOMXPath {
            if (in_array($pUrl, $this->checked) || in_array($pUrl, $this->excluded)) {
                return null;
            }

            $this->checked[] = $pUrl;

            $baseUrl = $pUrl;
            $xpath = PageHelper::getXPath($this->ch, $this->domain, $pUrl);

            if ($baseUrl != $pUrl) {
                if (in_array($pUrl, $this->checked) || in_array($pUrl, $this->excluded)) {
                    return null;
                }

                $this->checked[] = $pUrl;
            }

            return $xpath;
        }

        protected function processPageLinks(\DOMXPath $xpath) {
            $links = $xpath->evaluate('//a/@href');

            foreach ($links as $link) {
                $newUrl = PageHelper::domainRelativeURL($link->value);
                if (!$newUrl) {
                    continue;
                }

                if (!str_starts_with($newUrl, '/')) {
                    $newUrl = '/' . $newUrl;
                }

                $this->cachePageRec($newUrl);
            }
        }

        protected function cacheFilesInDir(string $dir) {
            if (in_array($dir, $this->excluded) || in_array($dir, $this->checked)) {
                return;
            }

            $files = FilesystemHelper::listFiles($dir);
            $this->checked[] = $dir;

            foreach ($files as $filename) {
                $path = $dir . '/' . $filename;

                if (is_dir($path)) {
                    $this->cacheFilesInDir($path);

                    continue;
                }

                $extension = pathinfo($path, PATHINFO_EXTENSION);
                if (!in_array($extension, self::EXTS)) {
                    continue;
                }

                error_log("processing file: " . $path);

                $modMarker = filemtime($path);
                $key = md5($path);

                $listed = array_key_exists($key, $this->listDescr);
                if ($listed && $this->listDescr[$key]->modMarker == $modMarker) {
                    $this->listDescr[$key]->processedAt = $this->processedAt;
                    $this->unchangedCtr++;

                    continue;
                }

                $url = mb_substr($path, $this->prefixLength);

                $rawText = $this->textExtractHelper->getText($path, $extension);
                if (!$rawText) {
                    continue;
                }

                $this->buildCaches($key, $rawText);

                $this->listDescr[$key] = new CacheDescr(
                    $filename,
                    $url,
                    CacheDescr::TYPE_FILE,
                    LangHelper::assumeFileLanguage($url),
                    $key,
                    $modMarker,
                    $this->processedAt
                );
            }
        }

        protected function buildCaches(string $key, string $rawText) {
            /** @var WordDescr[] $wordDescrs */
            $wordDescrs = TokenHelper::wordDescrs($rawText, $this->ignoredWords);

            $exactPos = 0;
            $tokenizedCtr = 0;

            /** @var string[] $tokenizedWords */
            $tokenizedWords = [];

            /** @var string[] $exactWords */
            $exactWords = [];

            /** @var WordOffsets[] $offsets */
            $offsets = [];

            foreach ($wordDescrs as $wordDescr) {
                $word = $wordDescr->word;
                $wordLength = strlen($word);
                $start = $wordDescr->offset;
                $end = $wordDescr->offset + $wordLength;

                $tokenizedPos = null;
                if (!$wordDescr->isIgnored) {
                    $tokenizedWords[] = $word;
                    $tokenizedPos = $tokenizedCtr++;
                }

                $offsets[] = new WordOffsets($start, $end, $word, $exactPos, $tokenizedPos);

                $exactWords[] = $word;
                $exactPos += $wordLength + 1;
            }

            $this->cacheStorage->putOffsets($key, $offsets);
            $this->cacheStorage->putTokenized($key, $tokenizedWords);
            $this->cacheStorage->putExact($key, implode(' ', $exactWords));
            $this->cacheStorage->putRaw($key, $rawText);

            $this->addedCtr++;
        }
    }
?>