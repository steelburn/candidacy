<?php

namespace App\Providers\AI;

use App\DTOs\AIResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenRouter AI Provider.
 * 
 * Provides access to multiple AI models through OpenRouter's unified API.
 *
 * @package App\Providers\AI
 */
class OpenRouterProvider extends BaseProvider
{
    protected string $apiKey;
    protected string $apiUrl = 'https://openrouter.ai/api/v1/chat/completions';
    protected string $defaultModel;

    public function __construct(array $config = [])
    {
        $this->loadSettings($config);
    }

    protected function loadSettings(array $config = []): void
    {
        $this->apiKey = $config['api_key'] ?? \Shared\Services\ConfigurationService::get(
            'ai.openrouter.api_key',
            env('OPENROUTER_API_KEY', '')
        );
        $this->defaultModel = $config['model'] ?? \Shared\Services\ConfigurationService::get(
            'ai.openrouter.model',
            env('OPENROUTER_MODEL', 'mistralai/mistral-7b-instruct')
        );
        $this->timeout = (int) \Shared\Services\ConfigurationService::get(
            'ai.generation.timeout',
            90
        );
        $this->apiUrl = $config['url'] ?? \Shared\Services\ConfigurationService::get(
            'ai.openrouter.base_url',
            env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1')
        );
    }

    public function getProviderName(): string
    {
        return 'openrouter';
    }

    public function getType(): string
    {
        return 'openrouter';
    }

    public function getBaseUrl(): string
    {
        return $this->apiUrl;
    }

    public function getDisplayName(): string
    {
        return 'OpenRouter';
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
        return true; // OpenRouter supports many models
    }

    public function listModels(): array
    {
        $cacheKey = 'openrouter_models_' . md5($this->apiKey);
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () {
            if (empty($this->apiKey)) {
                return $this->getDefaultModelList();
            }
            
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->timeout(10)->get('https://openrouter.ai/api/v1/models');
                
                if ($response->successful()) {
                    $data = $response->json();
                    return array_column($data['data'] ?? [], 'id');
                }
            } catch (\Exception $e) {
                Log::warning("Failed to list OpenRouter models: {$e->getMessage()}");
            }
            
            return $this->getDefaultModelList();
        });
    }

    protected function getDefaultModelList(): array
    {
        return [
            'mistralai/mistral-7b-instruct',
            'meta-llama/llama-3-8b-instruct',
            'google/gemma-7b-it',
            'anthropic/claude-3-haiku',
        ];
    }

    public function generate(string $prompt, array $options = []): AIResponse
    {
        if (empty($this->apiKey)) {
            return AIResponse::failure(
                provider: $this->getProviderName(),
                model: $options['model'] ?? $this->defaultModel,
                durationMs: 0,
                error: 'OpenRouter API key not configured'
            );
        }

        $model = $options['model'] ?? $this->defaultModel;
        $this->logRequestStart($model, strlen($prompt));

        return $this->executeWithTiming(function () use ($prompt, $model) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => env('APP_URL', 'http://localhost'),
                'X-Title' => 'Candidacy AI Recruitment'
            ])->timeout($this->timeout)->post("{$this->apiUrl}/chat/completions", [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            if (!$response->successful()) {
                throw new \Exception("OpenRouter API failed: {$response->body()}");
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
