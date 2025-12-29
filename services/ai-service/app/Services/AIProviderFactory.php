<?php

namespace App\Services;

use App\Contracts\AIProviderInterface;
use App\DTOs\AIResponse;
use Illuminate\Support\Facades\Log;

/**
 * AI Provider Factory - backward compatible facade over ProviderManager.
 * 
 * Maintains the original API while delegating to ProviderManager
 * for failover chain support.
 *
 * @package App\Services
 */
class AIProviderFactory
{
    protected ProviderManager $manager;
    protected ?AIProviderInterface $lastProvider = null;
    protected ?AIResponse $lastResponse = null;

    public function __construct()
    {
        $this->manager = new ProviderManager();
    }

    /**
     * Get the primary provider instance (backward compatibility).
     */
    public function getProvider(): AIProviderInterface
    {
        $providerName = \Shared\Services\ConfigurationService::get('ai.provider', env('AI_PROVIDER', 'ollama'));
        return $this->manager->getProvider($providerName) ?? $this->manager->getProvider('ollama');
    }

    /**
     * Get the provider manager for advanced operations.
     */
    public function getManager(): ProviderManager
    {
        return $this->manager;
    }

    /**
     * Get last response with metadata.
     */
    public function getLastResponse(): ?AIResponse
    {
        return $this->lastResponse;
    }

    /**
     * Generate text using failover chain (CV parsing, JD generation).
     */
    public function generate(string $prompt): string
    {
        $this->lastResponse = $this->manager->generateForService('cv_parsing', $prompt);
        $this->logResponse('generate');
        return $this->lastResponse->content;
    }

    /**
     * Generate for matching using faster model.
     */
    public function generateForMatching(string $prompt): string
    {
        $this->lastResponse = $this->manager->generateForService('matching', $prompt);
        $this->logResponse('matching');
        return $this->lastResponse->content;
    }

    /**
     * Generate for questionnaires.
     */
    public function generateForQuestionnaire(string $prompt): string
    {
        $this->lastResponse = $this->manager->generateForService('questions', $prompt);
        $this->logResponse('questionnaire');
        return $this->lastResponse->content;
    }

    /**
     * Generate for job descriptions.
     */
    public function generateForJobDescription(string $prompt): string
    {
        $this->lastResponse = $this->manager->generateForService('jd_generation', $prompt);
        $this->logResponse('jd_generation');
        return $this->lastResponse->content;
    }

    /**
     * Generate for question discussion.
     */
    public function generateForDiscussion(string $prompt): string
    {
        $this->lastResponse = $this->manager->generateForService('discussion', $prompt);
        $this->logResponse('discussion');
        return $this->lastResponse->content;
    }

    /**
     * Log response metadata.
     */
    protected function logResponse(string $operation): void
    {
        if (!$this->lastResponse) return;

        Log::info("AI generation completed", [
            'operation' => $operation,
            'provider' => $this->lastResponse->provider,
            'model' => $this->lastResponse->model,
            'duration_ms' => round($this->lastResponse->durationMs, 2),
            'success' => $this->lastResponse->success,
            'failover_attempt' => $this->lastResponse->failoverAttempt,
            'total_attempts' => $this->lastResponse->totalAttempts,
        ]);
    }
}
