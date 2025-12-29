<?php

namespace App\Providers\Parser;

use App\Contracts\DocumentParserInterface;
use App\DTOs\ParseResponse;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;

/**
 * Local DOCX Provider - PhpWord based extraction.
 *
 * @package App\Providers\Parser
 */
class LocalDocxProvider implements DocumentParserInterface
{
    public function getProviderName(): string { return 'local_docx'; }
    public function getDisplayName(): string { return 'Local DOCX Parser'; }
    public function supportsFileType(string $ext): bool { return in_array(strtolower($ext), ['docx', 'doc']); }
    public function isAvailable(): bool { return true; }

    public function parse(string $filePath): ParseResponse
    {
        $start = microtime(true);
        
        try {
            $phpWord = IOFactory::load($filePath);
            $text = '';
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }
            
            $pageCount = count($phpWord->getSections());
            $durationMs = (microtime(true) - $start) * 1000;

            return ParseResponse::success(trim($text), $this->getProviderName(), $pageCount, $durationMs);
        } catch (\Exception $e) {
            $durationMs = (microtime(true) - $start) * 1000;
            Log::error("Local DOCX parsing failed: {$e->getMessage()}");
            return ParseResponse::failure($this->getProviderName(), $durationMs, $e->getMessage());
        }
    }
}
