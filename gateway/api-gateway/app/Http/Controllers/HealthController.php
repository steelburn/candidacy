<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

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

        // Check Redis connectivity (if configured)
        if (env('REDIS_HOST')) {
            try {
                Redis::ping();
                $checks['redis'] = 'ok';
            } catch (\Exception $e) {
                $checks['redis'] = 'error: ' . $e->getMessage();
                $healthy = false;
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
