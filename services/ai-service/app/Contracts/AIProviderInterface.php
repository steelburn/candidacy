<?php

namespace App\Contracts;

use App\DTOs\AIResponse;

/**
 * Contract for AI providers.
 * 
 * All AI providers (LLM and document parsing) must implement this interface
 * to enable unified handling and failover chains.
 *
 * @package App\Contracts
 */
interface AIProviderInterface
{
    /**
     * Generate a response from the AI provider.
     *
     * @param string $prompt The prompt to send to the AI
     * @param array $options Provider-specific options (model, temperature, etc.)
     * @return AIResponse Response with content and metadata
     */
    public function generate(string $prompt, array $options = []): AIResponse;

    /**
     * Get the provider's unique identifier.
     *
     * @return string Provider name (e.g., 'ollama', 'openai', 'gemini')
     */
    public function getProviderName(): string;

    /**
     * Get the provider's display name for UI.
     *
     * @return string Human-readable name
     */
    public function getDisplayName(): string;

    /**
     * Get the provider type.
     *
     * @return string Either 'llm' or 'document'
     */
    public function getType(): string;

    /**
     * Check if the provider is currently available.
     * 
     * Used for failover decisions. Should be a quick health check.
     *
     * @return bool True if provider is ready to accept requests
     */
    public function isAvailable(): bool;

    /**
     * Get the default model for this provider.
     *
     * @return string Default model identifier
     */
    public function getDefaultModel(): string;

    /**
     * Check if the provider supports a specific model.
     *
     * @param string $model Model identifier to check
     * @return bool True if model is supported
     */
    public function supportsModel(string $model): bool;

    /**
     * List all available models for this provider.
     *
     * @return array List of model identifiers
     */
    public function listModels(): array;
    /**
     * Check if the provider has an API key configured.
     *
     * @return bool
     */
    public function hasApiKey(): bool;

    /**
     * Get the provider's base URL.
     *
     * @return string
     */
    public function getBaseUrl(): string;
}
