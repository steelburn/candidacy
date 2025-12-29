<?php

namespace App\Providers\AI;

use App\DTOs\AIResponse;
use Illuminate\Support\Facades\Http;

/**
 * llama.cpp Provider - local llama.cpp server.
 *
 * @package App\Providers\AI
 */
class LlamaCppProvider extends BaseProvider
{
    protected string $baseUrl;
    protected string $defaultModel;

    public function __construct(array $config = [])
    {
        $this->loadSettings($config);
    }

    protected function loadSettings(array $config = []): void
    {
        $this->baseUrl = $config['url'] ?? \Shared\Services\ConfigurationService::get('ai.llamacpp.base_url', env('LLAMACPP_BASE_URL', 'http://localhost:8080'));
        $this->defaultModel = $config['model'] ?? \Shared\Services\ConfigurationService::get('ai.llamacpp.model', 'local');
        $this->timeout = (int) \Shared\Services\ConfigurationService::get('ai.generation.timeout', 300);
    }

    public function getProviderName(): string { return 'llamacpp'; }
    public function getType(): string { return 'llamacpp'; }
    public function getDisplayName(): string { return 'llama.cpp'; }
    public function getDefaultModel(): string { return $this->defaultModel; }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function supportsModel(string $model): bool { return true; }
    public function listModels(): array { return [$this->defaultModel]; }

    public function generate(string $prompt, array $options = []): AIResponse
    {
        $model = $options['model'] ?? $this->defaultModel;
        $this->logRequestStart($model, strlen($prompt));

        return $this->executeWithTiming(function () use ($prompt, $model) {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/completion", [
                    'prompt' => $prompt,
                    'temperature' => 0.7,
                    'n_predict' => 2048,
                ]);

            if (!$response->successful()) {
                throw new \Exception("llama.cpp failed: {$response->body()}");
            }

            $data = $response->json();
            return [
                'content' => $data['content'] ?? '',
                'input_tokens' => $data['tokens_evaluated'] ?? null,
                'output_tokens' => $data['tokens_predicted'] ?? null,
            ];
        }, $model);
    }
}
