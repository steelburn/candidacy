<?php

namespace App\DTOs;

/**
 * DTO for document parsing responses.
 *
 * @package App\DTOs
 */
class ParseResponse
{
    public function __construct(
        public readonly string $text,
        public readonly string $provider,
        public readonly int $pageCount,
        public readonly float $durationMs,
        public readonly bool $success,
        public readonly ?string $error = null,
        public readonly int $failoverAttempt = 1,
        public readonly int $totalAttempts = 1,
    ) {}

    public static function success(
        string $text,
        string $provider,
        int $pageCount,
        float $durationMs,
        int $failoverAttempt = 1,
    ): self {
        return new self($text, $provider, $pageCount, $durationMs, true, null, $failoverAttempt);
    }

    public static function failure(string $provider, float $durationMs, string $error): self
    {
        return new self('', $provider, 0, $durationMs, false, $error);
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'provider' => $this->provider,
            'page_count' => $this->pageCount,
            'duration_ms' => $this->durationMs,
            'success' => $this->success,
            'error' => $this->error,
        ];
    }
}
