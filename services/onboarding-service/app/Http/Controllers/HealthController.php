<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     * 
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        $serviceName = env('APP_NAME', 'unknown-service');
        $checks = [];
        $healthy = true;

        // Check database connectivity
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error: ' . $e->getMessage();
            $healthy = false;
        }

        // Check Redis connectivity (if configured and Redis class exists)
        if (env('REDIS_HOST') && class_exists(\Redis::class)) {
            try {
                \Illuminate\Support\Facades\Redis::ping();
                $checks['redis'] = 'ok';
            } catch (\Exception $e) {
                $checks['redis'] = 'warning: ' . $e->getMessage();
                // Don't mark as unhealthy for Redis failures
            }
        }

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'service' => $serviceName,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
