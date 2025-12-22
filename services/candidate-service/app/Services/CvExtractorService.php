<?php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Storage;

class CvExtractorService
{
    /**
     * Extract text from uploaded CV file
     */
    public function extractText(string $filePath): ?string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        try {
            return match($extension) {
                'pdf' => $this->extractFromPdf($filePath),
                'docx', 'doc' => $this->extractFromDocx($filePath),
                'txt' => file_get_contents($filePath),
                default => null
            };
        } catch (\Exception $e) {
            \Log::error('CV text extraction failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract text from PDF file
     */
    private function extractFromPdf(string $filePath): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        
        // Clean up the text
        return $this->cleanText($text);
    }

    /**
     * Extract text from DOCX file
     */
    protected function extractFromDocx($filePath)
    {
        $phpWord = IOFactory::load($filePath);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                // Process TextRun elements
                if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                    foreach ($element->getElements() as $textElement) {
                        if (method_exists($textElement, 'getText')) {
                            $text .= $textElement->getText();
                        }
                    }
                    $text .= "\n";
                }
                // Process Text elements
                elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text .= $element->getText() . "\n";
                }
                // Process Table elements (common in resumes)
                elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                    foreach ($element->getRows() as $row) {
                        foreach ($row->getCells() as $cell) {
                            foreach ($cell->getElements() as $cellElement) {
                                if ($cellElement instanceof \PhpOffice\PhpWord\Element\TextRun) {
                                    foreach ($cellElement->getElements() as $textElement) {
                                        if (method_exists($textElement, 'getText')) {
                                            $text .= $textElement->getText();
                                        }
                                    }
                                } elseif ($cellElement instanceof \PhpOffice\PhpWord\Element\Text) {
                                    $text .= $cellElement->getText();
                                }
                            }
                            $text .= " "; // Space between cells
                        }
                        $text .= "\n"; // New line after each row
                    }
                }
            }
        }

        return $this->cleanText($text);
    }

    /**
     * Clean extracted text
     */
    private function cleanText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove special characters but keep punctuation
        $text = preg_replace('/[^\w\s\-.,@()]/u', '', $text);
        
        return trim($text);
    }
}
