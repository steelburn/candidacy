<?php

namespace App\DTOs;

/**
 * Data Transfer Object for AI responses.
 * 
 * Contains the response content along with metadata for tracking,
 * logging, and failover handling.
 *
 * @package App\DTOs
 */
class AIResponse
{
    /**
     * Create a new AI response instance.
     *
     * @param string $content The generated content
     * @param string $provider Provider identifier that generated this response
     * @param string $model Model identifier used
     * @param float $durationMs Time taken in milliseconds
     * @param bool $success Whether the request was successful
     * @param string|null $error Error message if failed
     * @param int|null $inputTokens Input token count (if available)
     * @param int|null $outputTokens Output token count (if available)
     * @param int $failoverAttempt Which attempt in the chain succeeded (1 = primary)
     * @param int $totalAttempts Total number of attempts made
     */
    public function __construct(
        public readonly string $content,
        public readonly string $provider,
        public readonly string $model,
        public readonly float $durationMs,
        public readonly bool $success,
        public readonly ?string $error = null,
        public readonly ?int $inputTokens = null,
        public readonly ?int $outputTokens = null,
        public readonly int $failoverAttempt = 1,
        public readonly int $totalAttempts = 1,
    ) {}

    /**
     * Create a successful response.
     */
    public static function success(
        string $content,
        string $provider,
        string $model,
        float $durationMs,
        ?int $inputTokens = null,
        ?int $outputTokens = null,
        int $failoverAttempt = 1,
        int $totalAttempts = 1,
    ): self {
        return new self(
            content: $content,
            provider: $provider,
            model: $model,
            durationMs: $durationMs,
            success: true,
            error: null,
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            failoverAttempt: $failoverAttempt,
            totalAttempts: $totalAttempts,
        );
    }

    /**
     * Create a failed response.
     */
    public static function failure(
        string $provider,
        string $model,
        float $durationMs,
        string $error,
        int $failoverAttempt = 1,
        int $totalAttempts = 1,
    ): self {
        return new self(
            content: '',
            provider: $provider,
            model: $model,
            durationMs: $durationMs,
            success: false,
            error: $error,
            inputTokens: null,
            outputTokens: null,
            failoverAttempt: $failoverAttempt,
            totalAttempts: $totalAttempts,
        );
    }

    /**
     * Convert to array for logging/API response.
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'provider' => $this->provider,
            'model' => $this->model,
            'duration_ms' => $this->durationMs,
            'success' => $this->success,
            'error' => $this->error,
            'input_tokens' => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'failover_attempt' => $this->failoverAttempt,
            'total_attempts' => $this->totalAttempts,
        ];
    }
}
