<?php
	namespace App\Helpers;
	
	use Exception;
    use PhpOffice\PhpSpreadsheet\IOFactory as XlsxIOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpWord\Element\Text;
    use PhpOffice\PhpWord\Element\TextRun;
    use PhpOffice\PhpWord\IOFactory as DocxIOFactory;
    use PhpOffice\PhpWord\PhpWord;
    use Smalot\PdfParser\Parser as PDFParser;

    require_once($_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php');

    /** This class provides methods to extract text content from the files of required
     * types
     */
	class TextExtractHelper {
        /** @var PDFParser PDF parser object */
		protected PDFParser $pdfParser;
		
		public function __construct() {
			$this->pdfParser = new PDFParser();
		}

        /**
         * @param string $path path to file
         * @param string $extension file extension
         * @return ?string file content or null
         */
		public function getText(string $path, string $extension): ?string {
            switch ($extension) {
				case 'pdf':
					try {
						$text = $this->pdfParser->parseFile($path)->getText();
					} catch (Exception) {
						return null;
					}
					break;
				case 'docx':
					try {
						$docx = DocxIOFactory::load($path);
						$text = self::getTextDocx($docx);
					} catch (Exception) {
						return null;
					}
					break;
				case 'xlsx':
                    try {
                        $xlsx = XlsxIOFactory::load($path);
                        $text = self::getTextXlsx($xlsx);
                     } catch (Exception) {
						return null;
					}

                    break;
                default:
					return null;
			}

			return TokenHelper::cleanUp($text);
		}

        /**
         * @param PhpWord $docx docx file handle
         * @return string file contents
         */
		protected static function getTextDocx(PhpWord $docx): string {
            $sections = $docx->getSections();
			$content = '';

            foreach ($sections as $section) {
                $sectEls = $section->getElements();

                foreach ($sectEls as $sectEl) {
					if ($sectEl instanceof TextRun) {
						$inSectEls = $sectEl->getElements();

						foreach ($inSectEls as $inSectEl) {
							if ($inSectEl instanceof Text) {
								$content .= $inSectEl->getText() . ' ';
							}
						}
					}
				}
			}
			
			return $content;
		}

        /**
         * @param Spreadsheet $xlsx xlsx file handle
         * @return string file contents
         * @throws \PhpOffice\PhpSpreadsheet\Exception
         */
        protected static function getTextXlsx(Spreadsheet $xlsx): string {
			$content = '';
			
			$sheetCount = $xlsx->getSheetCount();
			for ($i = 0; $i < $sheetCount; $i++) {
                $sheet = $xlsx->getSheet($i);

                $rowIt = $sheet->getRowIterator();

                foreach ($rowIt as $row) {
                    $cellIt = $row->getCellIterator();

					foreach ($cellIt as $cell) {
						$content .= $cell->getValue() . ' ';
					}
				}
			}
			
			return $content;
		}
	}