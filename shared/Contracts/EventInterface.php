<?php

namespace Shared\Contracts;

/**
 * Event interface for inter-service communication
 */
interface EventInterface
{
    /**
     * Get event name
     */
    public function getName(): string;

    /**
     * Get event payload
     */
    public function getPayload(): array;

    /**
     * Get event timestamp
     */
    public function getTimestamp(): int;

    /**
     * Serialize event to JSON
     */
    public function toJson(): string;
}
