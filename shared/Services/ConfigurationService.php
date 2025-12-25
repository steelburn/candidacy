<?php

namespace Shared\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ConfigurationService
{
    private static $instance = null;
    private $adminServiceUrl;
    private $cacheTtl = 3600; // 1 hour
    private $isSubscribed = false;

    private function __construct()
    {
        $this->adminServiceUrl = env('ADMIN_SERVICE_URL', 'http://admin-service:8080');
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get a configuration value
     */
    public static function get(string $key, $default = null)
    {
        $instance = self::getInstance();
        $cacheKey = "config:{$key}";

        return Cache::remember($cacheKey, $instance->cacheTtl, function () use ($instance, $key, $default) {
            try {
                $response = Http::timeout(2)->get("{$instance->adminServiceUrl}/api/settings/{$key}");

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['value'] ?? $default;
                }

                Log::warning("Failed to fetch configuration: {$key}", [
                    'status' => $response->status()
                ]);

                return $default;
            } catch (\Exception $e) {
                Log::error("Configuration fetch error: {$key}", [
                    'error' => $e->getMessage()
                ]);

                return $default;
            }
        });
    }

    /**
     * Get all configurations
     */
    public static function getAll(): array
    {
        $instance = self::getInstance();
        $cacheKey = "config:all";

        return Cache::remember($cacheKey, $instance->cacheTtl, function () use ($instance) {
            try {
                $response = Http::timeout(5)->get("{$instance->adminServiceUrl}/api/settings");

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['settings'] ?? [];
                }

                return [];
            } catch (\Exception $e) {
                Log::error("Failed to fetch all configurations", [
                    'error' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    /**
     * Get configurations by category
     */
    public static function getByCategory(string $category): array
    {
        $instance = self::getInstance();
        $cacheKey = "config:category:{$category}";

        return Cache::remember($cacheKey, $instance->cacheTtl, function () use ($instance, $category) {
            try {
                $response = Http::timeout(3)->get("{$instance->adminServiceUrl}/api/settings/category/{$category}");

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['settings'] ?? [];
                }

                return [];
            } catch (\Exception $e) {
                Log::error("Failed to fetch category configurations: {$category}", [
                    'error' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    /**
     * Get configurations by service scope
     */
    public static function getByScope(string $scope): array
    {
        $instance = self::getInstance();
        $cacheKey = "config:scope:{$scope}";

        return Cache::remember($cacheKey, $instance->cacheTtl, function () use ($instance, $scope) {
            try {
                $response = Http::timeout(3)->get("{$instance->adminServiceUrl}/api/settings/scope/{$scope}");

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['settings'] ?? [];
                }

                return [];
            } catch (\Exception $e) {
                Log::error("Failed to fetch scope configurations: {$scope}", [
                    'error' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    /**
     * Refresh a specific configuration (invalidate cache)
     */
    public static function refresh(string $key = null): void
    {
        if ($key) {
            Cache::forget("config:{$key}");
            Log::info("Configuration cache refreshed: {$key}");
        } else {
            // Clear all config caches
            Cache::forget('config:all');
            Log::info("All configuration caches cleared");
        }
    }

    /**
     * Subscribe to configuration change events via Redis Pub/Sub
     */
    public static function subscribe(): void
    {
        $instance = self::getInstance();

        if ($instance->isSubscribed) {
            return;
        }

        try {
            // Subscribe to configuration change events
            Redis::subscribe(['config:changed', 'config:changed:bulk', 'config:reload'], function ($message) {
                $data = json_decode($message, true);

                if (isset($data['key'])) {
                    // Single config change
                    Cache::forget("config:{$data['key']}");
                    Log::info("Configuration updated via Pub/Sub: {$data['key']}");
                } elseif (isset($data['changes'])) {
                    // Bulk changes
                    foreach ($data['changes'] as $key => $value) {
                        Cache::forget("config:{$key}");
                    }
                    Cache::forget('config:all');
                    Log::info("Bulk configuration update via Pub/Sub", [
                        'count' => count($data['changes'])
                    ]);
                } elseif (isset($data['action']) && $data['action'] === 'reload') {
                    // Full reload
                    Cache::forget('config:all');
                    Log::info("Configuration reload signal received");
                }
            });

            $instance->isSubscribed = true;
        } catch (\Exception $e) {
            Log::error("Failed to subscribe to configuration changes", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Start background listener for configuration changes
     * This should be called in a separate process/worker
     */
    public static function startListener(): void
    {
        Log::info("Starting configuration change listener...");

        while (true) {
            try {
                self::subscribe();
            } catch (\Exception $e) {
                Log::error("Configuration listener error", [
                    'error' => $e->getMessage()
                ]);

                // Wait before retrying
                sleep(5);
            }
        }
    }
}
