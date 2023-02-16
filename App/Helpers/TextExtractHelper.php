<?php
    namespace App\Helpers;

    require_once($_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php');

    class TextExtractHelper {
        protected \Smalot\PdfParser\Parser $pdfParser;

        public function __construct() {
            $this->pdfParser = new \Smalot\PdfParser\Parser();
        }

        public function getText(string $path, string $extension): ?string {
            $text = '';

            switch ($extension) {
                case 'pdf':
                    try {
                        $text = $this->pdfParser->parseFile($path)->getText();
                    } catch (\Exception $e) {
                        return null;
                    }
                    break;
                case 'docx':
                    try {
                        /** @var \PhpOffice\PhpWord\PhpWord $docx; */
                        $docx = \PhpOffice\PhpWord\IOFactory::load($path);
                        $text = self::getTextDocx($docx);
                    } catch (\PhpOffice\PhpWord\Exception\InvalidImageException $e) {
                        return null;
                    }
                    break;
                case 'xlsx':
                    /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet */
                    $xlsx = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                    $text = self::getTextXlsx($xlsx);
                default:
                    return null;
            }

            return TokenHelper::stripWhitespaces($text);
        }

        protected static function getTextDocx(\PhpOffice\PhpWord\PhpWord $docx): string {
            /** @var \PhpOffice\PhpWord\Element\Section[] $sections */
            $sections = $docx->getSections();
            $content = '';

            /** @var \PhpOffice\PhpWord\Element\Section $section */
            foreach ($sections as $section) {
                /** @var \PhpOffice\PhpWord\Element\AbstractElement[] $sectEls */
                $sectEls = $section->getElements();

                /** @var \PhpOffice\PhpWord\Element\AbstractElement $sectEl */
                foreach ($sectEls as $sectEl) {
                    if ($sectEl instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        /** @var \PhpOffice\PhpWord\Element\AbstractElement[] $inSectEls */
                        $inSectEls = $sectEl->getElements();

                        /** @var \PhpOffice\PhpWord\Element\AbstractElement $inSectEl */
                        foreach ($inSectEls as $inSectEl) {
                            if ($inSectEl instanceof \PhpOffice\PhpWord\Element\Text) {
                                $content .= $inSectEl->getText() . ' ';
                            }
                        }
                    }
                }
            }

            return $content;
        }

        protected static function getTextXlsx(
            \PhpOffice\PhpSpreadsheet\Spreadsheet $xlsx
        ): string {
            $content = '';

            $sheetCount = $xlsx->getSheetCount();
            for ($i = 0; $i < $sheetCount; $i++) {
                /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet */
                $sheet = $xlsx->getSheet($i);

                /** @var \PhpOffice\PhpSpreadsheet\Worksheet\RowIterator $rowIt */
                $rowIt = $sheet->getRowIterator();

                /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Row $row*/
                foreach ($rowIt as $row) {
                    /** @var \PhpOffice\PhpSpreadsheet\Worksheet\RowCellIterator $cellIt */
                    $cellIt = $row->getCellIterator();

                    /** @var \PhpOffice\PhpSpreadsheet\Cell\Cell $cell */
                    foreach ($cellIt as $cell) {
                        $content .= $cell->getValue() . ' ';
                    }
                }
            }

            return $content;
        }
    }
?>