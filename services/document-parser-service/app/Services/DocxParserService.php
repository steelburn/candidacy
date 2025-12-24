<?php
namespace App\Services;
use PhpOffice\PhpWord\IOFactory;

class DocxParserService
{
    public function extractText(string $path): array
    {
        $phpWord = IOFactory::load($path);
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) $text .= $element->getText() . "\n";
            }
        }
        return ['text' => trim($text), 'page_count' => count($phpWord->getSections())];
    }
}
