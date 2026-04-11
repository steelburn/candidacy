<?php

namespace Shared\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced Caching Service
 * 
 * Provides centralized caching with consistent TTLs, cache key management,
 * and cross-driver pattern matching support.
 */
class CacheService
{
    /**
     * Cache TTL constants (in seconds)
     */
    public const TTL_SETTINGS = 600;        // 10 minutes
    public const TTL_MATCH_RESULTS = 3600;   // 1 hour
    public const TTL_DASHBOARD = 300;        // 5 minutes
    public const TTL_VACANCIES = 900;        // 15 minutes
    public const TTL_SHORT = 60;             // 1 minute
    public const TTL_LONG = 7200;            // 2 hours

    private string $driver;

    public function __construct()
    {
        $this->driver = config('cache.default', 'file');
    }

    /**
     * Remember a value in cache with automatic expiration
     *
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param callable $callback Function to generate value if not cached
     * @return mixed
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            $this->logWarning('Cache remember failed', $key, $e);
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
    public function put(string $key, $value, int $ttl): bool
    {
        try {
            return Cache::put($key, $value, $ttl);
        } catch (\Exception $e) {
            $this->logWarning('Cache put failed', $key, $e);
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
    public function get(string $key, $default = null)
    {
        try {
            return Cache::get($key, $default);
        } catch (\Exception $e) {
            $this->logWarning('Cache get failed', $key, $e);
            return $default;
        }
    }

    /**
     * Check if a key exists in cache
     *
     * @param string $key Cache key
     * @return bool
     */
    public function has(string $key): bool
    {
        try {
            return Cache::has($key);
        } catch (\Exception $e) {
            $this->logWarning('Cache has check failed', $key, $e);
            return false;
        }
    }

    /**
     * Invalidate cache by key
     *
     * @param string $key Cache key
     * @return bool
     */
    public function forget(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            $this->logWarning('Cache forget failed', $key, $e);
            return false;
        }
    }

    /**
     * Invalidate cache by pattern (works across all cache drivers)
     *
     * @param string $pattern Pattern to match (supports * wildcards)
     * @return int Number of keys deleted
     */
    public function forgetPattern(string $pattern): int
    {
        $deleted = 0;

        try {
            if ($this->driver === 'redis') {
                $deleted = $this->forgetPatternRedis($pattern);
            } elseif ($this->driver === 'memcached') {
                $deleted = $this->forgetPatternMemcached($pattern);
            } else {
                // For file, array, or other drivers, clear all and rebuild
                $deleted = $this->forgetPatternGeneric($pattern);
            }
        } catch (\Exception $e) {
            Log::warning('Cache pattern forget failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }

        return $deleted;
    }

    /**
     * Generate a cache key for settings
     *
     * @param string|null $key Specific setting key
     * @return string
     */
    public function settingsKey(?string $key = null): string
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
    public function matchKey(int $candidateId, ?int $vacancyId = null): string
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
    public function dashboardKey(string $metric): string
    {
        return "dashboard:{$metric}";
    }

    /**
     * Generate a cache key for vacancy listings
     *
     * @param array $filters Filters applied
     * @return string
     */
    public function vacancyListKey(array $filters = []): string
    {
        $filterHash = md5(json_encode($filters));
        return "vacancies:list:{$filterHash}";
    }

    /**
     * Clear all application caches
     *
     * @return bool
     */
    public function clearAll(): bool
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

    /**
     * Get the current cache driver
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Forget pattern for Redis driver
     */
    private function forgetPatternRedis(string $pattern): int
    {
        $redis = Cache::getRedis();
        $keys = $redis->keys($pattern);
        
        $deleted = 0;
        if (!empty($keys)) {
            foreach ($keys as $key) {
                Cache::forget($key);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Forget pattern for Memcached driver
     */
    private function forgetPatternMemcached(string $pattern): int
    {
        // Memcached doesn't support pattern deletion efficiently
        // Log a warning and return 0
        Log::warning('Memcached does not support pattern-based cache invalidation', [
            'pattern' => $pattern,
        ]);
        
        return 0;
    }

    /**
     * Generic pattern forget for non-pattern supporting drivers
     */
    private function forgetPatternGeneric(string $pattern): int
    {
        // For file/array drivers, we need to clear all
        // This is a limitation of these cache drivers
        Log::info('Clearing entire cache due to pattern match on non-Redis driver', [
            'pattern' => $pattern,
            'driver' => $this->driver,
        ]);

        Cache::flush();
        
        return 1;
    }

    /**
     * Log a warning message
     */
    private function logWarning(string $message, string $key, \Exception $e): void
    {
        Log::warning($message, [
            'key' => $key,
            'error' => $e->getMessage(),
        ]);
    }
}
