<?php

namespace App\Providers\AI;

use App\Contracts\AIProviderInterface;
use App\DTOs\AIResponse;
use Illuminate\Support\Facades\Log;

/**
 * Base class for AI providers with common functionality.
 * 
 * Provides shared utilities for timing, logging, and error handling.
 *
 * @package App\Providers\AI
 */
abstract class BaseProvider implements AIProviderInterface
{
    protected string $baseUrl = '';
    protected string $defaultModel;
    protected int $timeout = 300;

    /**
     * Get provider type - override in subclasses for document providers.
     */
    public function getType(): string
    {
        return 'llm';
    }

    /**
     * Get the provider's base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Check if API key is configured.
     */
    public function hasApiKey(): bool
    {
        return property_exists($this, 'apiKey') && !empty($this->apiKey);
    }

    /**
     * Execute a timed request with standard error handling.
     *
     * @param callable $callback The request to execute
     * @param string $model Model being used
     * @return AIResponse
     */
    protected function executeWithTiming(callable $callback, string $model): AIResponse
    {
        $startTime = microtime(true);
        
        try {
            $result = $callback();
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            Log::info("AI request completed", [
                'provider' => $this->getProviderName(),
                'model' => $model,
                'duration_ms' => round($durationMs, 2),
                'success' => true,
            ]);
            
            return AIResponse::success(
                content: $result['content'],
                provider: $this->getProviderName(),
                model: $model,
                durationMs: $durationMs,
                inputTokens: $result['input_tokens'] ?? null,
                outputTokens: $result['output_tokens'] ?? null,
            );
        } catch (\Exception $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;
            
            Log::error("AI request failed", [
                'provider' => $this->getProviderName(),
                'model' => $model,
                'duration_ms' => round($durationMs, 2),
                'error' => $e->getMessage(),
            ]);
            
            return AIResponse::failure(
                provider: $this->getProviderName(),
                model: $model,
                durationMs: $durationMs,
                error: $e->getMessage(),
            );
        }
    }

    /**
     * Log the start of an AI request.
     */
    protected function logRequestStart(string $model, int $promptLength): void
    {
        Log::info("AI request initiated", [
            'provider' => $this->getProviderName(),
            'model' => $model,
            'prompt_length' => $promptLength,
        ]);
    }
}
