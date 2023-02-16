# Dizzy search

This is a showcase of a simple and clean search and crawl engine which was developed in accordance with very specific demands of a customer. They had a legacy website with thousands of poorly bound HTML pages and files (mainly PDF, DOCX, XLSX) and they needed a plain way to categorize the data and search through it. The main search criteria must be the length of a continuous sequence of keywords.

### Features

- Clean architecture backend
- Filesystem cache storage (including query cache)
- Recursive scanning of picked directories and ignoring the unnecessary ones
- Recursive crawling of the current website
- Storing the data in a uniform 'tokenized' format
- Exact search or search by keyword sequences
- Further scaling (i.e. by adding ElasticSearch or another storage) is straightforward

### Credits
[PHPOffice](https://github.com/PHPOffice) and [PDFParser](https://github.com/smalot/pdfparser) are used to collect *.docx, *.xlsx and *.pdf files content respectively.
