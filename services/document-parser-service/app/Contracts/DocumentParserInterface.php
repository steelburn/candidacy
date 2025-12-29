<?php

namespace App\Contracts;

use App\DTOs\ParseResponse;

/**
 * Contract for document parsing providers.
 *
 * @package App\Contracts
 */
interface DocumentParserInterface
{
    public function parse(string $filePath): ParseResponse;
    public function getProviderName(): string;
    public function getDisplayName(): string;
    public function isAvailable(): bool;
    public function supportsFileType(string $extension): bool;
}
