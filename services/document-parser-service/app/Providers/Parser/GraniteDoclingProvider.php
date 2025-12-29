<?php

namespace App\Providers\Parser;

use App\Contracts\DocumentParserInterface;
use App\DTOs\ParseResponse;
use App\Services\PdfParserService;
use Illuminate\Support\Facades\Log;

/**
 * Granite Docling PDF Provider - AI-based PDF parsing.
 *
 * @package App\Providers\Parser
 */
class GraniteDoclingProvider implements DocumentParserInterface
{
    protected PdfParserService $pdfService;

    public function __construct()
    {
        $this->pdfService = new PdfParserService();
    }

    public function getProviderName(): string { return 'granite_docling'; }
    public function getDisplayName(): string { return 'Granite Docling (AI)'; }
    public function supportsFileType(string $ext): bool { return strtolower($ext) === 'pdf'; }

    public function isAvailable(): bool
    {
        $useDocling = \Shared\Services\ConfigurationService::get('document_parser.use_granite_docling', true);
        if (!$useDocling) return false;

        try {
            $ollamaUrl = \Shared\Services\ConfigurationService::get('ai.ollama.url', 'http://ollama:11434');
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get("{$ollamaUrl}/api/tags");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function parse(string $filePath): ParseResponse
    {
        $start = microtime(true);
        
        try {
            // Use extractWithDocling specifically
            $result = $this->pdfService->extractText($filePath);
            $durationMs = (microtime(true) - $start) * 1000;
            
            if (($result['parser'] ?? '') !== 'granite-docling') {
                // Fell back to basic parser - treat as not available
                throw new \Exception("Granite Docling not used, fell back to basic parser");
            }

            return ParseResponse::success(
                $result['text'],
                $this->getProviderName(),
                $result['page_count'] ?? 1,
                $durationMs
            );
        } catch (\Exception $e) {
            $durationMs = (microtime(true) - $start) * 1000;
            Log::error("Granite Docling parsing failed: {$e->getMessage()}");
            return ParseResponse::failure($this->getProviderName(), $durationMs, $e->getMessage());
        }
    }
}
