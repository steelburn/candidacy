<?php

namespace Shared\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced Caching Service
 * 
 * Provides centralized caching with consistent TTLs and cache key management
 */
class CacheService
{
    /**
     * Cache TTL constants (in seconds)
     */
    const TTL_SETTINGS = 600;        // 10 minutes
    const TTL_MATCH_RESULTS = 3600;  // 1 hour
    const TTL_DASHBOARD = 300;       // 5 minutes
    const TTL_VACANCIES = 900;       // 15 minutes
    const TTL_SHORT = 60;            // 1 minute
    const TTL_LONG = 7200;           // 2 hours

    /**
     * Remember a value in cache with automatic expiration
     *
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param callable $callback Function to generate value if not cached
     * @return mixed
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache remember failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to direct execution if cache fails
            return $callback();
        }
    }

    /**
     * Store a value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds
     * @return bool
     */
    public static function put(string $key, $value, int $ttl): bool
    {
        try {
            return Cache::put($key, $value, $ttl);
        } catch (\Exception $e) {
            Log::warning('Cache put failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get a value from cache
     *
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        try {
            return Cache::get($key, $default);
        } catch (\Exception $e) {
            Log::warning('Cache get failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return $default;
        }
    }

    /**
     * Invalidate cache by key
     *
     * @param string $key Cache key
     * @return bool
     */
    public static function forget(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::warning('Cache forget failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Invalidate cache by pattern (tags or prefix)
     *
     * @param string $pattern Pattern to match
     * @return bool
     */
    public static function forgetPattern(string $pattern): bool
    {
        try {
            // For Redis, we can use pattern matching
            if (config('cache.default') === 'redis') {
                $redis = Cache::getRedis();
                $keys = $redis->keys($pattern);
                
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        Cache::forget($key);
                    }
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::warning('Cache pattern forget failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate a cache key for settings
     *
     * @param string|null $key Specific setting key
     * @return string
     */
    public static function settingsKey(?string $key = null): string
    {
        return $key ? "settings:{$key}" : "settings:all";
    }

    /**
     * Generate a cache key for matches
     *
     * @param int $candidateId Candidate ID
     * @param int|null $vacancyId Optional vacancy ID
     * @return string
     */
    public static function matchKey(int $candidateId, ?int $vacancyId = null): string
    {
        return $vacancyId 
            ? "match:candidate:{$candidateId}:vacancy:{$vacancyId}"
            : "matches:candidate:{$candidateId}";
    }

    /**
     * Generate a cache key for dashboard metrics
     *
     * @param string $metric Metric name
     * @return string
     */
    public static function dashboardKey(string $metric): string
    {
        return "dashboard:{$metric}";
    }

    /**
     * Generate a cache key for vacancy listings
     *
     * @param array $filters Filters applied
     * @return string
     */
    public static function vacancyListKey(array $filters = []): string
    {
        $filterHash = md5(json_encode($filters));
        return "vacancies:list:{$filterHash}";
    }

    /**
     * Clear all application caches
     *
     * @return bool
     */
    public static function clearAll(): bool
    {
        try {
            return Cache::flush();
        } catch (\Exception $e) {
            Log::error('Cache clear all failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
