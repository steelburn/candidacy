<?php

namespace App\Providers\AI;

use App\DTOs\AIResponse;
use Illuminate\Support\Facades\Http;

/**
 * Azure OpenAI Provider.
 *
 * @package App\Providers\AI
 */
class AzureAIProvider extends BaseProvider
{
    protected string $apiKey;
    protected string $endpoint;
    protected string $deployment;
    protected string $apiVersion = '2024-02-15-preview';

    public function __construct(array $config = [])
    {
        $this->loadSettings($config);
    }

    protected function loadSettings(array $config = []): void
    {
        $this->apiKey = $config['api_key'] ?? \Shared\Services\ConfigurationService::get('ai.azure.api_key', env('AZURE_OPENAI_API_KEY', ''));
        $this->endpoint = $config['url'] ?? \Shared\Services\ConfigurationService::get('ai.azure.endpoint', env('AZURE_OPENAI_ENDPOINT', ''));
        $this->deployment = $config['model'] ?? \Shared\Services\ConfigurationService::get('ai.azure.deployment', env('AZURE_OPENAI_DEPLOYMENT', 'gpt-4'));
        $this->timeout = (int) \Shared\Services\ConfigurationService::get('ai.generation.timeout', 120);
    }

    public function getProviderName(): string { return 'azure'; }
    public function getType(): string { return 'azure'; }
    public function getDisplayName(): string { return 'Azure OpenAI'; }
    public function getDefaultModel(): string { return $this->deployment; }
    public function isAvailable(): bool { return !empty($this->apiKey) && !empty($this->endpoint); }
    public function supportsModel(string $model): bool { return true; }
    public function listModels(): array { return [$this->deployment]; }

    public function generate(string $prompt, array $options = []): AIResponse
    {
        if (!$this->isAvailable()) {
            return AIResponse::failure($this->getProviderName(), $this->deployment, 0, 'Azure OpenAI not configured');
        }

        $model = $options['model'] ?? $this->deployment;
        $url = "{$this->endpoint}/openai/deployments/{$model}/chat/completions?api-version={$this->apiVersion}";
        
        $this->logRequestStart($model, strlen($prompt));

        return $this->executeWithTiming(function () use ($prompt, $url, $model) {
            $response = Http::withHeaders(['api-key' => $this->apiKey])
                ->timeout($this->timeout)
                ->post($url, [
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7,
                ]);

            if (!$response->successful()) {
                throw new \Exception("Azure API failed: {$response->body()}");
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
