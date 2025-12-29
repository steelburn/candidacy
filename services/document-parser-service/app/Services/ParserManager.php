<?php

namespace App\Services;

use App\Contracts\DocumentParserInterface;
use App\DTOs\ParseResponse;
use App\Providers\Parser\{GraniteDoclingProvider, LocalPdfProvider, LocalDocxProvider};
use Illuminate\Support\Facades\Log;

/**
 * Manages document parsing providers with failover support.
 *
 * @package App\Services
 */
class ParserManager
{
    /** @var array<string, DocumentParserInterface> */
    protected array $providers = [];

    /** @var array<string, array> Failover chains per file type */
    protected array $chains = [];

    public function __construct()
    {
        $this->registerProviders();
        $this->loadChains();
    }

    protected function registerProviders(): void
    {
        $this->providers = [
            'granite_docling' => new GraniteDoclingProvider(),
            'local_pdf' => new LocalPdfProvider(),
            'local_docx' => new LocalDocxProvider(),
        ];
    }

    protected function loadChains(): void
    {
        $this->chains = [
            'pdf' => [
                ['provider' => 'granite_docling'],
                ['provider' => 'local_pdf'],
            ],
            'docx' => [
                ['provider' => 'local_docx'],
            ],
            'doc' => [
                ['provider' => 'local_docx'],
            ],
        ];

        // Override from config if available
        $configured = \Shared\Services\ConfigurationService::get('document_parser.chains', null);
        if ($configured && is_array($configured)) {
            $this->chains = array_merge($this->chains, $configured);
        }
    }

    public function parse(string $filePath): ParseResponse
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $chain = $this->chains[$extension] ?? $this->chains['pdf'];

        $attempt = 0;
        $totalAttempts = count($chain);
        $lastError = null;

        foreach ($chain as $config) {
            $attempt++;
            $providerName = $config['provider'];
            $provider = $this->providers[$providerName] ?? null;

            if (!$provider || !$provider->isAvailable() || !$provider->supportsFileType($extension)) {
                Log::info("Parser skipped", ['provider' => $providerName, 'extension' => $extension]);
                continue;
            }

            $response = $provider->parse($filePath);

            if ($response->success) {
                return new ParseResponse(
                    $response->text, $response->provider, $response->pageCount,
                    $response->durationMs, true, null, $attempt, $totalAttempts
                );
            }

            $lastError = $response->error;
            Log::warning("Parser failed, trying next", ['provider' => $providerName, 'error' => $lastError]);
        }

        return ParseResponse::failure('none', 0, "All parsers failed. Last: {$lastError}");
    }

    public function getProvider(string $name): ?DocumentParserInterface
    {
        return $this->providers[$name] ?? null;
    }

    public function getAllProviders(): array
    {
        return $this->providers;
    }
}
