<?php

namespace App\Providers\AI;

use App\DTOs\AIResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI Provider.
 * 
 * Direct integration with OpenAI's API for GPT models.
 *
 * @package App\Providers\AI
 */
class OpenAIProvider extends BaseProvider
{
    protected string $apiKey;
    protected string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    protected string $defaultModel;

    public function __construct(array $config = [])
    {
        $this->loadSettings($config);
    }

    protected function loadSettings(array $config = []): void
    {
        $this->apiKey = $config['api_key'] ?? \Shared\Services\ConfigurationService::get(
            'ai.openai.api_key',
            env('OPENAI_API_KEY', '')
        );
        $this->defaultModel = $config['model'] ?? \Shared\Services\ConfigurationService::get(
            'ai.openai.model',
            env('OPENAI_MODEL', 'gpt-4o-mini')
        );
        $this->timeout = (int) \Shared\Services\ConfigurationService::get(
            'ai.generation.timeout',
            120
        );
        $this->apiUrl = $config['url'] ?? \Shared\Services\ConfigurationService::get(
            'ai.openai.base_url',
            env('OPENAI_BASE_URL', 'https://api.openai.com/v1')
        );
    }

    public function getProviderName(): string
    {
        return 'openai';
    }

    public function getType(): string
    {
        return 'openai';
    }

    public function getBaseUrl(): string
    {
        return $this->apiUrl;
    }

    public function getDisplayName(): string
    {
        return 'OpenAI';
    }

    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function supportsModel(string $model): bool
    {
        return str_starts_with($model, 'gpt-');
    }

    public function listModels(): array
    {
        $cacheKey = 'openai_models_' . md5($this->apiKey);
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () {
            if (empty($this->apiKey)) {
                return $this->getDefaultModelList();
            }
            
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->timeout(10)->get('https://api.openai.com/v1/models');
                
                if ($response->successful()) {
                    $data = $response->json();
                    $models = array_column($data['data'] ?? [], 'id');
                    // Filter to chat models only
                    return array_values(array_filter($models, fn($m) => str_starts_with($m, 'gpt-')));
                }
            } catch (\Exception $e) {
                Log::warning("Failed to list OpenAI models: {$e->getMessage()}");
            }
            
            return $this->getDefaultModelList();
        });
    }

    protected function getDefaultModelList(): array
    {
        return ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo'];
    }

    public function generate(string $prompt, array $options = []): AIResponse
    {
        if (empty($this->apiKey)) {
            return AIResponse::failure(
                provider: $this->getProviderName(),
                model: $options['model'] ?? $this->defaultModel,
                durationMs: 0,
                error: 'OpenAI API key not configured'
            );
        }

        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $this->logRequestStart($model, strlen($prompt));

        return $this->executeWithTiming(function () use ($prompt, $model, $temperature) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout($this->timeout)->post("{$this->apiUrl}/chat/completions", [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => $temperature,
            ]);

            if (!$response->successful()) {
                throw new \Exception("OpenAI API failed: {$response->body()}");
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            return [
                'content' => $content,
                'input_tokens' => $data['usage']['prompt_tokens'] ?? null,
                'output_tokens' => $data['usage']['completion_tokens'] ?? null,
            ];
        }, $model);
    }
}
