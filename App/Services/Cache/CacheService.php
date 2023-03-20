<?php
	namespace App\Services\Cache;
	
	use App\Entities\Cache\EntityCache;
    use App\Entities\Cache\EntityCacheIndex;
    use App\Entities\Cache\EntityRawCache;
    use App\Entities\URL;
    use App\Helpers\ArrayHelper;
    use App\Helpers\FilesystemHelper;
    use App\Helpers\IgnoredWordsHelper;
    use App\Helpers\LangHelper;
	use App\Helpers\WebHelper;
	use App\Helpers\TextExtractHelper;
	use App\Helpers\TokenHelper;
	use App\Storage\Cache\FileCacheStorage;
	use App\Storage\Cache\ICacheStorage;
    use CurlHandle;

    /** This class contains all the methods related to entity cache */
	class CacheService {
		/** @var string Page content storage selector */
		protected const XPATH_CONTENT = "//header";
		
		/** @var array[string]null Allowed extensions */
        protected const EXTENSIONS = [
			'pdf'  => null,
			'docx' => null,
			'xlsx' => null
		];
		
		/** @var string Language GET param key */
        protected const LANG_KEY = 'l';

        /** @var ICacheStorage Cache repository */
		protected ICacheStorage $cacheStorage;

        /** @var int Length of document root */
		protected int $rootPathLength;

        /** @var TextExtractHelper Helper class for extracting text from files */
		protected TextExtractHelper $textExtractHelper;
		
		/** @var array[string]null
         * For files: paths to the directories that should be processed
         * For pages: known mod_markers of the pages (stored to avoid repetition)
         */
		protected array $included = [];
		
		/** @var array[string]null Paths to the entities excluded from processing  */
		protected array $excluded = [];
		
		/** @var array[string]null Paths to the entities that have already been checked */
		protected array $checked = [];
		
		/** @var array[string]null Array of words that should be ignored in exact cache */
		protected array $ignoredWords = [];

        /** @var string Current site domain name */
		protected string $domain;

        /** @var string Current language */
		protected string $lang;

        /** @var CurlHandle Curl handle for site crawling */
		protected CurlHandle $ch;
		
		/** @var EntityCacheIndex Entity cache index, empty or loaded from repository */
		protected EntityCacheIndex $cacheIndex;

        /** @var int Cache processing timestamp */
		protected int $processedAt;

        /** @var int Number of entries added to the cache */
		protected int $addedCtr = 0;

        /** @var int Number of entities removed from the cache */
		protected int $removedCtr = 0;

        /** @var int Number of entities left unchanged in the cache */
		protected int $unchangedCtr = 0;

        /** @var int Caching start time */
        protected int $startTime = 0;
		
		public function __construct() {
			$this->cacheStorage = new FileCacheStorage();
			
			$this->rootPathLength = mb_strlen($_SERVER['DOCUMENT_ROOT']);
			$this->textExtractHelper = new TextExtractHelper();
		}
		
		/**
         * This function is called before other caching functions and pre-initializes
         * the data needed
		 * @param string[] $ignoredWords Array (word => null) of ignored words
		 */
		public function prepareCaching(array $ignoredWords): void {
            $this->startTime = hrtime(true);

			$this->ignoredWords = $ignoredWords;
			$this->cacheIndex = $this->cacheStorage->list();
			$this->processedAt = time();

			$this->addedCtr = 0;
			$this->removedCtr = 0;
			$this->unchangedCtr = 0;
		}
		
		/**
         * This functions performs file caching based on the included and excluded dirs
		 * @param string[] $included Array of dirs that should be scanned
		 * @param string[] $excluded Array of ignored dirs, has priority over included
		 */
		public function cacheFiles(array $included, array $excluded): void {
			$this->included = ArrayHelper::toHashmap(function (string $value): string {
				return $_SERVER['DOCUMENT_ROOT'] . $value;
			}, $included);
			
			$this->excluded = ArrayHelper::toHashmap(function (string $value): string {
				return $_SERVER['DOCUMENT_ROOT'] . $value;
			}, $excluded);
			
			$this->checked = [];
			
			foreach ($this->included as $dir => $null) {
				$this->cacheFilesInDir($dir);
			}
		}
		
		/**
         * This function recursively scans and caches all pages of the current site
         * starting from index and skipping the excluded ones
		 * @param string[] $excluded Pages that should not be scanned
		 */
		public function cachePages(array $excluded): void {
			libxml_use_internal_errors(true);
			
			$this->included = [];
			
			$this->domain = WebHelper::domain();
			
			$this->ch = curl_init();
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($this->ch, CURLOPT_HEADER, true);
			
			foreach (LangHelper::LANGUAGES as $lang) {
				$this->checked = [];
				$this->excluded = ArrayHelper::toHashmap(function (
					string $value
				) use ($lang): string {
                    $url = new URL($value);

                    $url->setGetParam(self::LANG_KEY, $lang);

                    return $url->toString();
				}, $excluded);
				
				$this->lang = $lang;
				
				$this->cachePageRec(new URL('/'));
			}
			
			libxml_clear_errors();
		}
		
		/**
         * This function finishes the caching process and returns the statistics
		 * @return array
         *     0 => added elements (int),
         *     1 => removed elements (int),
         *     2 => unchanged elements (int)
         *     3 => unchanged elements (int)
		 */
		public function finish(): array {
            $arr = $this->cacheIndex->toArray();

			foreach ($arr as $key => $value) {
				if ($value[EntityCacheIndex::PROCESSED_AT] >= $this->processedAt) {
					continue;
				}
				
				$this->cacheStorage->remove($key);
				$this->cacheIndex->remove($key);
				$this->removedCtr++;
			}
			
			$this->cacheStorage->putList($this->cacheIndex);

            $endTime = hrtime(true);

            return [
                $this->addedCtr,
                $this->removedCtr,
                $this->unchangedCtr,
                ($endTime - $this->startTime) / 1e9
            ];
		}

        /**
         * This function caches the given page and all same site pages linked on it
         * @param URL $url URL
         * @return void
         */
		protected function cachePageRec(URL $url): void {
            $url->setGetParam(self::LANG_KEY, $this->lang);
            $urlStr = $url->toString(true);

            if (!$this->shouldScan($urlStr)) {
                return;
            }

            $baseUrl = $urlStr;
            $xpath = WebHelper::getXPath($this->ch, $this->domain, $urlStr);

            if (!$xpath || ($baseUrl != $urlStr && !$this->shouldScan($urlStr))) {
                return;
            }

            $links = $xpath->evaluate('//a/@href');
            foreach ($links as $link) {
                $url = new URL($link->value);
                if (!$url->belongsToDomain()) {
                    continue;
                }

                $this->cachePageRec($url);
            }
			
			$query = self::XPATH_CONTENT . WebHelper::XPATH_TEXT_ELEMENTS;
			$rawText = WebHelper::rawTextXPath($xpath, $query);

            $name = WebHelper::rawTextXPath($xpath, "//title/text()");
            if (!$name) {
                $name = $urlStr;
            }
			
			$modMarker = md5($rawText);
            $key = md5('page:' . $this->lang . ':' . $urlStr);

			if (array_key_exists($modMarker, $this->included)) {
				return;
			}
			
			$this->included[$modMarker] = null;

            $this->checkAndBuildCaches(
                $name,
                EntityCacheIndex::TYPE_PAGE,
                $key,
                $modMarker,
                $urlStr,
                $rawText
            );
		}

        /**
         * This function recursively scans the given directory and caches the valid files
         * @param string $dir Directory to be scanned
         * @return void
         */
		protected function cacheFilesInDir(string $dir): void {
            if (!$this->shouldScan($dir)) {
                return;
            }

			$files = FilesystemHelper::listFiles($dir);
			foreach ($files as $filename) {
                $path = $dir . '/' . $filename;

                if (is_dir($path)) {
                    $this->cacheFilesInDir($path);

                    continue;
                }

                $extension = pathinfo($path, PATHINFO_EXTENSION);
                if (!array_key_exists($extension, self::EXTENSIONS)) {
                    continue;
                }

                $modMarker = filemtime($path);
                $key = md5($path);

                $url = mb_substr($path, $this->rootPathLength);

                $rawText = $this->textExtractHelper->getText($path, $extension);
                if (!$rawText) {
                    continue;
                }

                $this->checkAndBuildCaches(
                    $filename,
                    EntityCacheIndex::TYPE_FILE,
                    $key,
                    $modMarker,
                    $url,
                    $rawText
                );
            }
		}

        /**
         * @param string $entity Directory path or page URL, or another unique identifier
         * @return bool Whether the entity has already been processed or scanned
         */
        protected function shouldScan(string $entity): bool {
            $excluded = array_key_exists($entity, $this->excluded);
            $checked = array_key_exists($entity, $this->checked);
            if ($excluded || $checked) {
                return false;
            }

            $this->checked[$entity] = null;

            return true;
        }

        /**
         * This function checks if the cache needs to be built and builds it
         * @param string $name Entity name
         * @param string $type Entity type
         * @param string $key Cache key
         * @param string $modMarker File mod time or page content hash
         * @param string $url Entity URL
         * @param string $rawText Entity raw contents
         * @return void
         */
        protected function checkAndBuildCaches(
            string $name,
            string $type,
            string $key,
            string $modMarker,
            string $url,
            string $rawText
        ): void {
            if ($this->cacheIndex->exists($key)) {
                $el =& $this->cacheIndex->get($key);

                if ($el[EntityCacheIndex::MOD_MARKER] == $modMarker) {
                    $el[EntityCacheIndex::PROCESSED_AT] = $this->processedAt;
                    $this->unchangedCtr++;

                    return;
                }
            }

            $this->buildCaches($key, $rawText);

            $this->cacheIndex->add(
                $name,
                $url,
                $type,
                LangHelper::assumeFileLanguage($url),
                $key,
                $modMarker,
                $this->processedAt
            );
        }

        /**
         * This function saves the newly built file caches to the storage
         * @param string $key Cache key
         * @param string $rawText Raw entity contents
         * @return void
         */
		protected function buildCaches(string $key, string $rawText): void {
            $baseRes = TokenHelper::wordsWithOffsets($rawText);
            if (!$baseRes) {
                return;
            }

            $cache = new EntityCache();
            $rawCache = new EntityRawCache();
            $prevNonIgnoredWord = null;
            $numBaseRes = count($baseRes);

            $rawCache->setRawText($rawText);

            for ($i = 0; $i < $numBaseRes; $i++) {
                list ($word, $offset) = $baseRes[$i];
                if (!$word) {
                    continue;
                }

                $lcWord = mb_strtolower($word);

                $isIgnored = array_key_exists($lcWord, $this->ignoredWords);
                $isAcronym = array_key_exists($word, IgnoredWordsHelper::ACRONYMS);

                $cache->add($lcWord, $i, $prevNonIgnoredWord);
                $rawCache->add($i, $offset, $offset + strlen($lcWord));

                if ($isIgnored && $isAcronym) {
                    $cache->add(mb_strtoupper($word), $i, $prevNonIgnoredWord);
                    $prevNonIgnoredWord = $i;

                    continue;
                }

                if (!$isIgnored) {
                    $prevNonIgnoredWord = $i;
                }
            }

            $this->cacheStorage->putCache($key, $cache);
            $this->cacheStorage->putRaw($key, $rawCache);

            $this->addedCtr++;
		}
	}