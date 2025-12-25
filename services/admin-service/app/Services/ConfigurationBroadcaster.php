<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ConfigurationBroadcaster
{
    /**
     * Broadcast a single configuration change
     */
    public function broadcast(string $key, $value, array $metadata = []): void
    {
        $message = json_encode([
            'key' => $key,
            'value' => $value,
            'timestamp' => now()->toIso8601String(),
            'metadata' => $metadata
        ]);

        try {
            Redis::publish('config:changed', $message);
            
            Log::info('Configuration change broadcasted', [
                'key' => $key,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast configuration change', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Broadcast multiple configuration changes
     */
    public function broadcastBulk(array $changes): void
    {
        $message = json_encode([
            'changes' => $changes,
            'timestamp' => now()->toIso8601String(),
            'bulk' => true
        ]);

        try {
            Redis::publish('config:changed:bulk', $message);
            
            Log::info('Bulk configuration changes broadcasted', [
                'count' => count($changes),
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast bulk configuration changes', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Broadcast configuration reload signal
     */
    public function broadcastReload(): void
    {
        $message = json_encode([
            'action' => 'reload',
            'timestamp' => now()->toIso8601String()
        ]);

        try {
            Redis::publish('config:reload', $message);
            Log::info('Configuration reload signal broadcasted');
        } catch (\Exception $e) {
            Log::error('Failed to broadcast reload signal', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
