<?php

namespace App\Providers\Parser;

use App\Contracts\DocumentParserInterface;
use App\DTOs\ParseResponse;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

/**
 * Local PDF Provider - smalot-pdfparser based extraction.
 *
 * @package App\Providers\Parser
 */
class LocalPdfProvider implements DocumentParserInterface
{
    public function getProviderName(): string { return 'local_pdf'; }
    public function getDisplayName(): string { return 'Local PDF Parser'; }
    public function supportsFileType(string $ext): bool { return strtolower($ext) === 'pdf'; }
    public function isAvailable(): bool { return true; }

    public function parse(string $filePath): ParseResponse
    {
        $start = microtime(true);
        
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            $pageCount = count($pdf->getPages());
            $durationMs = (microtime(true) - $start) * 1000;

            return ParseResponse::success($text, $this->getProviderName(), $pageCount, $durationMs);
        } catch (\Exception $e) {
            $durationMs = (microtime(true) - $start) * 1000;
            Log::error("Local PDF parsing failed: {$e->getMessage()}");
            return ParseResponse::failure($this->getProviderName(), $durationMs, $e->getMessage());
        }
    }
}
