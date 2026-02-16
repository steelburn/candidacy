<?php

namespace App\Providers\AI;

use App\DTOs\AIResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Ollama AI Provider.
 * 
 * Provides integration with local Ollama instance for LLM operations.
 * Supports multiple named instances with different configurations.
 *
 * @package App\Providers\AI
 */
class OllamaProvider extends BaseProvider
{
    protected string $instanceName;
    protected string $baseUrl;
    protected string $defaultModel;
    protected int $timeout;
    protected float $temperature;
    protected int $contextLength;

    /**
     * Create new Ollama provider instance.
     * 
     * @param array $config Optional config override: name, url, model, timeout
     */
    public function __construct(array $config = [])
    {
        $this->instanceName = $config['name'] ?? 'ollama';
        $this->loadSettings($config);
    }

    /**
     * Load settings from config array or configuration service.
     */
    protected function loadSettings(array $config = []): void
    {
        $this->baseUrl = $config['url'] ?? \Shared\Services\ConfigurationService::get(
            'ai.ollama.url',
            env('OLLAMA_URL', 'http://ollama:11434')
        );
        $this->defaultModel = $config['model'] ?? \Shared\Services\ConfigurationService::get(
            'ai.ollama.model.default',
            env('OLLAMA_MODEL', 'mistral')
        );
        $this->timeout = (int)($config['timeout'] ?? \Shared\Services\ConfigurationService::get(
            'ai.generation.timeout',
            300
        ));
        $this->temperature = (float)($config['temperature'] ?? \Shared\Services\ConfigurationService::get(
            'ai.generation.temperature',
            0.7
        ));
        $this->contextLength = (int)($config['context_length'] ?? \Shared\Services\ConfigurationService::get(
            'ai.generation.context_length',
            8192
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName(): string
    {
        return $this->instanceName;
    }

    public function getType(): string
    {
        return 'ollama';
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return $this->instanceName === 'ollama' ? 'Ollama' : "Ollama ({$this->instanceName})";
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");
            return $response->successful();
        }
        catch (\Exception $e) {
            Log::warning("Ollama availability check failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsModel(string $model): bool
    {
        $models = $this->listModels();
        return in_array($model, $models);
    }

    /**
     * {@inheritdoc}
     */
    public function listModels(): array
    {
        $cacheKey = 'ollama_models_' . md5($this->baseUrl);

        return Cache::remember($cacheKey, 300, function () {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/tags");

            if ($response->successful()) {
                $data = $response->json();
                return array_column($data['models'] ?? [], 'name');
            }

            throw new \Exception("Failed to fetch models: " . $response->status());
        });
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $prompt, array $options = []): AIResponse
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? $this->temperature;
        $contextLength = $options['context_length'] ?? $this->contextLength;

        $this->logRequestStart($model, strlen($prompt));

        return $this->executeWithTiming(function () use ($prompt, $model, $temperature, $contextLength) {
            $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/generate", [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'num_ctx' => $contextLength,
                    'temperature' => $temperature,
                    'repeat_penalty' => 1.5,
                    'top_k' => 40,
                    'top_p' => 0.9,
                ]
            ]);

            if (!$response->successful()) {
                throw new \Exception("Ollama API failed with status {$response->status()}: {$response->body()}");
            }

            $data = $response->json();
            $content = $data['response'] ?? '';

            if (empty($content)) {
                throw new \Exception("Empty response from Ollama");
            }

            return [
                'content' => $content,
                'input_tokens' => $data['prompt_eval_count'] ?? null,
                'output_tokens' => $data['eval_count'] ?? null,
            ];
        }, $model);
    }

    /**
     * Get matching-specific model name.
     */
    public function getMatchingModel(): string
    {
        return \Shared\Services\ConfigurationService::get(
            'ai.ollama.model.matching',
            $this->defaultModel
        );
    }

    public function getQuestionnaireModel(): string
    {
        return \Shared\Services\ConfigurationService::get(
            'ai.ollama.model.questionnaire',
            $this->getMatchingModel()
        );
    }
}