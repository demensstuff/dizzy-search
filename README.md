# Dizzy search

This is a showcase of a simple and clean search and crawl engine which was developed in accordance with very specific demands of a customer. They had a legacy website with thousands of poorly bound HTML pages and files (PDF, DOCX, XLSX) and they needed a plain way to categorize the data and search through it. The main search criteria must be the length of a continuous sequence of keywords.

### Features

- Clean architecture backend
- Filesystem cache storage (including query cache)
- Small cache size (thanks to [igbinary](https://github.com/igbinary/igbinary))
- Nearly constant time search within the given cache (thanks to hashmaps)
- Recursive scanning of picked directories and ignoring the unnecessary ones
- Recursive crawling of the current website
- Storing the data in a uniform 'tokenized' format
- Exact search or search by keyword sequences
- Further scaling (i.e. by adding ElasticSearch or another storage) is straightforward

### Requirements
- PHP 8.2
- [Composer](https://getcomposer.org/)
- Database [supported](https://www.php.net/manual/en/pdo.drivers.php) by PDO

### Usage
1. Run `composer install`
2. Put your database connection info to `.config/db/db.json` (check out `example.db.json`)
3. The cache will be stored inside `.cache`, so make sure your web server has permissions to write there
4. Optionally add some files to be cached and put your frontend inside `public_html`


### Example
```
require_once($_SERVER['DOCUMENT_ROOT'] . '/../App/autoload.php');

use App\UseCases\CacheUC;
use App\UseCases\SearchUC;

$cacheUC = new CacheUC();

// $includedDirs = array of relative paths to the directories that should be scanned for files
// $excludedDirs = array of relative paths to the directories that should not be scanned for files
// $excludedPages = array of relative URLs to the pages that should not be scanned

// Store the options
$dirResult = $cacheUC->setDirs($includedDirs, $excludedDirs, $excludedPages);

// Start caching process
$cachingResult = $cacheUC->buildCache();

$searchUC = new SearchUC();

// Get 20 most searched queries
$topQueries = $searchUC->topQueries(20);

// Search for 'Lorem ipsum dolor sit amet' in the cache, return the results on the 2nd page (10 results per page)
$searchResult = $searchUC->search('Lorem ipsum dolor sit amet', 2, 10);
```

### Credits
[PHPOffice](https://github.com/PHPOffice) and [PDFParser](https://github.com/smalot/pdfparser) are used to collect *.docx, *.xlsx and *.pdf files content respectively.
