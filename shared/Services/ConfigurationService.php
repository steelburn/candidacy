<?php

namespace Shared\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Configuration Service
 * 
 * Provides centralized configuration management with caching and
 * real-time updates via Redis Pub/Sub.
 */
class ConfigurationService
{
    private const DEFAULT_TTL = 3600;
    private const DEFAULT_TIMEOUT = 3;

    private string $adminServiceUrl;
    private int $cacheTtl;
    private int $timeout;

    public function __construct(?string $adminServiceUrl = null, ?int $cacheTtl = null, ?int $timeout = null)
    {
        $this->adminServiceUrl = $adminServiceUrl ?? config('services.admin_service_url', 'http://admin-service:8080');
        $this->cacheTtl = $cacheTtl ?? config('services.admin_service_ttl', self::DEFAULT_TTL);
        $this->timeout = $timeout ?? self::DEFAULT_TIMEOUT;
    }

    /**
     * Get a configuration value
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = "config:{$key}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($key, $default) {
            return $this->fetchFromAdminService("/api/settings/{$key}", $default);
        });
    }

    /**
     * Get all configurations
     */
    public function getAll(): array
    {
        $cacheKey = 'config:all';

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            return $this->fetchFromAdminService('/api/settings', []);
        });
    }

    /**
     * Get configurations by category
     */
    public function getByCategory(string $category): array
    {
        $cacheKey = "config:category:{$category}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($category) {
            return $this->fetchFromAdminService("/api/settings/category/{$category}", []);
        });
    }

    /**
     * Get configurations by service scope
     */
    public function getByScope(string $scope): array
    {
        $cacheKey = "config:scope:{$scope}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($scope) {
            return $this->fetchFromAdminService("/api/settings/scope/{$scope}", []);
        });
    }

    /**
     * Refresh a specific configuration (invalidate cache)
     */
    public function refresh(?string $key = null): void
    {
        if ($key) {
            Cache::forget("config:{$key}");
            Log::info("Configuration cache refreshed: {$key}");
        } else {
            Cache::forget('config:all');
            Log::info("All configuration caches cleared");
        }
    }

    /**
     * Subscribe to configuration change events via Redis Pub/Sub
     * 
     * @return \React\Promise\Deferred|null Returns null if already subscribed or on error
     */
    public function subscribe(?callable $callback = null): void
    {
        try {
            $channels = ['config:changed', 'config:changed:bulk', 'config:reload'];
            
            foreach ($channels as $channel) {
                Redis::subscribe([$channel], function ($message) use ($callback) {
                    $this->handleConfigChange($message, $callback);
                });
            }
        } catch (\Exception $e) {
            Log::error("Failed to subscribe to configuration changes", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle incoming configuration change message
     */
    protected function handleConfigChange(string $message, ?callable $callback = null): void
    {
        $data = json_decode($message, true);

        if (!$data) {
            return;
        }

        if (isset($data['key'])) {
            Cache::forget("config:{$data['key']}");
            Log::info("Configuration updated via Pub/Sub: {$data['key']}");
        } elseif (isset($data['changes'])) {
            foreach ($data['changes'] as $key => $value) {
                Cache::forget("config:{$key}");
            }
            Cache::forget('config:all');
            Log::info("Bulk configuration update via Pub/Sub", [
                'count' => count($data['changes'])
            ]);
        } elseif (isset($data['action']) && $data['action'] === 'reload') {
            Cache::forget('config:all');
            Log::info("Configuration reload signal received");
        }

        if ($callback) {
            $callback($data);
        }
    }

    /**
     * Fetch configuration from admin service
     */
    private function fetchFromAdminService(string $endpoint, $default)
    {
        try {
            $response = Http::timeout($this->timeout)->get("{$this->adminServiceUrl}{$endpoint}");

            if ($response->successful()) {
                $data = $response->json();
                return $data['value'] ?? $data['settings'] ?? $default;
            }

            Log::warning("Failed to fetch configuration: {$endpoint}", [
                'status' => $response->status()
            ]);

            return $default;
        } catch (\Exception $e) {
            Log::error("Configuration fetch error: {$endpoint}", [
                'error' => $e->getMessage()
            ]);

            return $default;
        }
    }
}
