<?php

namespace App\Services;

use App\Contracts\AIProviderInterface;
use App\DTOs\AIResponse;
use App\Models\AIProvider;
use App\Providers\AI\{
    OllamaProvider,
    OpenRouterProvider,
    OpenAIProvider,
    GeminiProvider,
    AzureAIProvider,
    LiteLLMProvider,
    LlamaCppProvider
};
use Illuminate\Support\Facades\Log;

/**
 * Manages AI providers with failover chain support.
 * 
 * Supports multiple named instances of the same provider type.
 *
 * @package App\Services
 */
class ProviderManager
{
    /** @var array<string, AIProviderInterface> */
    protected array $providers = [];

    /** @var array<string, array> Service type to provider chain mapping */
    protected array $serviceChains = [];

    /** @var array<string, string> Provider type to class mapping */
    protected array $providerClasses = [
        'ollama' => OllamaProvider::class,
        'openrouter' => OpenRouterProvider::class,
        'openai' => OpenAIProvider::class,
        'gemini' => GeminiProvider::class,
        'azure' => AzureAIProvider::class,
        'litellm' => LiteLLMProvider::class,
        'llamacpp' => LlamaCppProvider::class,
    ];

    public function __construct()
    {
        $this->registerDefaultProviders();
        $this->loadCustomInstances();
        $this->loadServiceChains();
    }

    /**
     * Register default providers (one instance per type).
     */
    protected function registerDefaultProviders(): void
    {
        $this->providers = [
            'ollama' => new OllamaProvider(),
            'openrouter' => new OpenRouterProvider(),
            'openai' => new OpenAIProvider(),
            'gemini' => new GeminiProvider(),
            'azure' => new AzureAIProvider(),
            'litellm' => new LiteLLMProvider(),
            'llamacpp' => new LlamaCppProvider(),
        ];
    }

    /**
     * Load custom provider instances from database.
     */
    protected function loadCustomInstances(): void
    {
        try {
            $instances = AIProvider::where('is_enabled', true)->get();
            
            foreach ($instances as $instance) {
                $type = $instance->type ?? 'ollama';
                $class = $this->providerClasses[$type] ?? null;
                
                if (!$class) continue;
                
                $config = array_merge(
                    ['name' => $instance->name],
                    $instance->config ?? [],
                    ['url' => $instance->base_url]
                );
                
                // Allow overriding default providers with DB config
                $this->providers[$instance->name] = new $class($config);
            }
        } catch (\Exception $e) {
            Log::debug("Could not load custom provider instances: {$e->getMessage()}");
        }
    }

    /**
     * Register a custom provider instance.
     */
    public function registerInstance(string $name, string $type, array $config): ?AIProviderInterface
    {
        $class = $this->providerClasses[$type] ?? null;
        if (!$class) return null;

        $config['name'] = $name;
        $this->providers[$name] = new $class($config);
        return $this->providers[$name];
    }

    /**
     * Load service-to-provider chains from configuration.
     */
    protected function loadServiceChains(): void
    {
        // Default chains - can be overridden via admin settings
        $defaultChains = [
            'cv_parsing' => [['provider' => 'ollama', 'model' => null]],
            'matching' => [['provider' => 'ollama', 'model' => null]],
            'jd_generation' => [['provider' => 'ollama', 'model' => null]],
            'questions' => [['provider' => 'ollama', 'model' => null]],
            'discussion' => [['provider' => 'ollama', 'model' => null]],
        ];

        // Load from config service
        $configuredChains = \Shared\Services\ConfigurationService::get('ai.service_chains', null);
        
        if (is_string($configuredChains)) {
            $configuredChains = json_decode($configuredChains, true);
        }
        
        if ($configuredChains && is_array($configuredChains)) {
            $this->serviceChains = array_merge($defaultChains, $configuredChains);
        } else {
            $this->serviceChains = $defaultChains;
        }
    }

    /**
     * Get a specific provider by name.
     */
    public function getProvider(string $name): ?AIProviderInterface
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * Get all registered providers.
     * @return array<string, AIProviderInterface>
     */
    public function getAllProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get available (healthy) providers.
     * @return array<string, AIProviderInterface>
     */
    public function getAvailableProviders(): array
    {
        return array_filter($this->providers, fn($p) => $p->isAvailable());
    }

    /**
     * Generate with failover chain for a service type.
     */
    public function generateForService(string $serviceType, string $prompt, array $options = []): AIResponse
    {
        $chain = $this->serviceChains[$serviceType] ?? $this->serviceChains['cv_parsing'];
        
        $attempt = 0;
        $totalAttempts = count($chain);
        $lastError = null;

        foreach ($chain as $config) {
            $attempt++;
            $providerName = $config['provider'];
            $model = $config['model'] ?? null;
            
            $provider = $this->getProvider($providerName);
            
            if (!$provider) {
                Log::warning("Provider not found in chain", ['provider' => $providerName, 'service' => $serviceType]);
                continue;
            }

            if (!$provider->isAvailable()) {
                Log::info("Provider unavailable, trying next", ['provider' => $providerName, 'attempt' => $attempt]);
                continue;
            }

            $opts = $options;
            if ($model) {
                $opts['model'] = $model;
            }

            $response = $provider->generate($prompt, $opts);

            if ($response->success) {
                // Log the successful request
                $this->logRequest($serviceType, $providerName, $model, $response);
                
                // Return with updated failover metadata
                return new AIResponse(
                    content: $response->content,
                    provider: $response->provider,
                    model: $response->model,
                    durationMs: $response->durationMs,
                    success: true,
                    error: null,
                    inputTokens: $response->inputTokens,
                    outputTokens: $response->outputTokens,
                    failoverAttempt: $attempt,
                    totalAttempts: $totalAttempts,
                );
            }

            $lastError = $response->error;
            Log::warning("Provider failed, trying next", [
                'provider' => $providerName,
                'error' => $lastError,
                'attempt' => $attempt,
            ]);
        }

        return AIResponse::failure(
            provider: 'none',
            model: 'none',
            durationMs: 0,
            error: "All providers failed. Last error: {$lastError}",
            failoverAttempt: $totalAttempts,
            totalAttempts: $totalAttempts,
        );
    }

    /**
     * Set service chain configuration.
     */
    public function setServiceChain(string $serviceType, array $chain): void
    {
        $this->serviceChains[$serviceType] = $chain;
    }

    /**
     * Get current service chains.
     */
    public function getServiceChains(): array
    {
        return $this->serviceChains;
    }

    /**
     * Log a request to the database for metrics.
     */
    protected function logRequest(string $serviceType, string $providerName, ?string $model, AIResponse $response): void
    {
        try {
            \App\Models\AIRequestLog::log([
                'service_type' => $serviceType,
                'provider_id' => null, // Will be populated when providers are in DB
                'model_id' => null,
                'input_tokens' => $response->inputTokens,
                'output_tokens' => $response->outputTokens,
                'duration_ms' => (int) $response->durationMs,
                'success' => $response->success,
                'failover_attempt' => $response->failoverAttempt,
                'total_attempts' => $response->totalAttempts,
                'error_message' => $response->error,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to log AI request: {$e->getMessage()}");
        }
    }
}
