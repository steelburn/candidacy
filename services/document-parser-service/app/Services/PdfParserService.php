<?php
namespace App\Services;
use Smalot\PdfParser\Parser;

class PdfParserService
{
    public function extractText(string $path): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($path);
        return ['text' => $pdf->getText(), 'page_count' => count($pdf->getPages())];
    }
}
