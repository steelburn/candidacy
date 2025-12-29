<?php

namespace App\Providers\AI;

use App\DTOs\AIResponse;
use Illuminate\Support\Facades\Http;

/**
 * Google Gemini AI Provider.
 *
 * @package App\Providers\AI
 */
class GeminiProvider extends BaseProvider
{
    protected string $apiKey;
    protected string $defaultModel;

    public function __construct(array $config = [])
    {
        $this->loadSettings($config);
    }

    protected function loadSettings(array $config = []): void
    {
        $this->apiKey = $config['api_key'] ?? \Shared\Services\ConfigurationService::get(
            'ai.gemini.api_key',
            env('GEMINI_API_KEY', '')
        );
        $this->defaultModel = $config['model'] ?? \Shared\Services\ConfigurationService::get(
            'ai.gemini.model',
            env('GEMINI_MODEL', 'gemini-1.5-flash')
        );
        $this->timeout = (int) \Shared\Services\ConfigurationService::get(
            'ai.generation.timeout',
            120
        );
        $this->baseUrl = $config['url'] ?? \Shared\Services\ConfigurationService::get(
            'ai.gemini.base_url',
            env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta')
        );
    }

    public function getProviderName(): string { return 'gemini'; }
    public function getType(): string { return 'gemini'; }
    public function getDisplayName(): string { return 'Google Gemini'; }
    public function getDefaultModel(): string { return $this->defaultModel; }
    public function isAvailable(): bool { return !empty($this->apiKey); }
    public function supportsModel(string $model): bool { return str_starts_with($model, 'gemini-'); }
    public function listModels(): array { return ['gemini-1.5-flash', 'gemini-1.5-pro', 'gemini-pro']; }

    public function generate(string $prompt, array $options = []): AIResponse
    {
        if (empty($this->apiKey)) {
            return AIResponse::failure($this->getProviderName(), $this->defaultModel, 0, 'Gemini API key not configured');
        }

        $model = $options['model'] ?? $this->defaultModel;
        $apiUrl = "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}";
        
        $this->logRequestStart($model, strlen($prompt));

        return $this->executeWithTiming(function () use ($prompt, $apiUrl, $model) {
            $response = Http::timeout($this->timeout)->post($apiUrl, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.7],
            ]);

            if (!$response->successful()) {
                throw new \Exception("Gemini API failed: {$response->body()}");
            }

            $data = $response->json();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return [
                'content' => $content,
                'input_tokens' => $data['usageMetadata']['promptTokenCount'] ?? null,
                'output_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? null,
            ];
        }, $model);
    }
}
