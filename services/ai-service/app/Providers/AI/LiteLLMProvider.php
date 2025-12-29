<?php

namespace App\Providers\AI;

use App\DTOs\AIResponse;
use Illuminate\Support\Facades\Http;

/**
 * LiteLLM Provider - proxy for multiple AI backends.
 *
 * @package App\Providers\AI
 */
class LiteLLMProvider extends BaseProvider
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;

    public function __construct(array $config = [])
    {
        $this->loadSettings($config);
    }

    protected function loadSettings(array $config = []): void
    {
        $this->baseUrl = $config['url'] ?? \Shared\Services\ConfigurationService::get('ai.litellm.base_url', env('LITELLM_BASE_URL', 'http://localhost:4000'));
        $this->apiKey = $config['api_key'] ?? \Shared\Services\ConfigurationService::get('ai.litellm.api_key', env('LITELLM_API_KEY', ''));
        $this->defaultModel = $config['model'] ?? \Shared\Services\ConfigurationService::get('ai.litellm.model', 'gpt-3.5-turbo');
        $this->timeout = (int) \Shared\Services\ConfigurationService::get('ai.generation.timeout', 120);
    }

    public function getProviderName(): string { return 'litellm'; }
    public function getType(): string { return 'litellm'; }
    public function getDisplayName(): string { return 'LiteLLM'; }
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
    
    public function listModels(): array
    {
        $cacheKey = 'litellm_models_list';
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () {
            try {
                $response = Http::timeout(10)->get("{$this->baseUrl}/models");
                
                if ($response->successful()) {
                    $data = $response->json();
                    return array_column($data['data'] ?? [], 'id');
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to list LiteLLM models: {$e->getMessage()}");
            }
            
            return [$this->defaultModel];
        });
    }

    public function generate(string $prompt, array $options = []): AIResponse
    {
        $model = $options['model'] ?? $this->defaultModel;
        $this->logRequestStart($model, strlen($prompt));

        return $this->executeWithTiming(function () use ($prompt, $model) {
            $headers = ['Content-Type' => 'application/json'];
            if (!empty($this->apiKey)) {
                $headers['Authorization'] = 'Bearer ' . $this->apiKey;
            }

            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);

            if (!$response->successful()) {
                throw new \Exception("LiteLLM failed: {$response->body()}");
            }

            $data = $response->json();
            return [
                'content' => $data['choices'][0]['message']['content'] ?? '',
                'input_tokens' => $data['usage']['prompt_tokens'] ?? null,
                'output_tokens' => $data['usage']['completion_tokens'] ?? null,
            ];
        }, $model);
    }
}
