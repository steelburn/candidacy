<?php

namespace Shared\Traits;

/**
 * Trait HasJsonFields
 * 
 * Provides consistent JSON field handling for Eloquent models.
 * Automatically handles encoding/decoding, comma-separated strings,
 * and type safety for JSON columns.
 */
trait HasJsonFields
{
    /**
     * Set a JSON attribute value.
     * Handles arrays, comma-separated strings, and JSON strings.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setJsonAttribute(string $key, $value): void
    {
        if ($value === null) {
            $this->attributes[$key] = null;
            return;
        }

        // If it's already an array, encode it
        if (is_array($value)) {
            $this->attributes[$key] = json_encode($value);
            return;
        }

        // If it's a string, check if it's valid JSON
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            
            // If it decodes to an array, it's valid JSON - store as-is
            if (is_array($decoded)) {
                $this->attributes[$key] = $value;
                return;
            }
            
            // If it's a comma-separated string, convert to JSON array
            if (strpos($value, ',') !== false) {
                $array = array_map('trim', explode(',', $value));
                $this->attributes[$key] = json_encode($array);
                return;
            }
            
            // Single value string - wrap in array
            $this->attributes[$key] = json_encode([$value]);
            return;
        }

        // For other types, try to encode
        $this->attributes[$key] = json_encode($value);
    }

    /**
     * Get a JSON attribute value as an array.
     *
     * @param string $key
     * @return array|null
     */
    public function getJsonAttribute(string $key): ?array
    {
        $value = $this->attributes[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($value)) {
            return $value;
        }

        return [];
    }

    /**
     * Merge new values into a JSON array attribute.
     *
     * @param string $key
     * @param array $values
     * @return void
     */
    public function mergeJsonAttribute(string $key, array $values): void
    {
        $current = $this->getJsonAttribute($key) ?? [];
        $merged = array_unique(array_merge($current, $values));
        $this->setJsonAttribute($key, $merged);
    }

    /**
     * Check if a JSON array attribute contains a value.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function jsonAttributeContains(string $key, $value): bool
    {
        $array = $this->getJsonAttribute($key) ?? [];
        return in_array($value, $array, true);
    }

    /**
     * Get a JSON attribute as a comma-separated string.
     *
     * @param string $key
     * @return string
     */
    public function getJsonAttributeAsString(string $key): string
    {
        $array = $this->getJsonAttribute($key) ?? [];
        return implode(', ', $array);
    }

    /**
     * Normalize JSON field data from request input.
     * Handles both array and string inputs consistently.
     *
     * @param mixed $value
     * @param bool $allowCommaSeparated
     * @return string|null
     */
    public static function normalizeJsonField($value, bool $allowCommaSeparated = true): ?string
    {
        if ($value === null) {
            return null;
        }

        // Already an array - encode it
        if (is_array($value)) {
            return json_encode($value);
        }

        // String value
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            
            // Valid JSON string - keep as-is
            if (is_array($decoded)) {
                return $value;
            }
            
            // Comma-separated string
            if ($allowCommaSeparated && strpos($value, ',') !== false) {
                $array = array_map('trim', explode(',', $value));
                return json_encode(array_filter($array));
            }
            
            // Single value - wrap in array
            if (!empty($value)) {
                return json_encode([$value]);
            }
        }

        return null;
    }
}
