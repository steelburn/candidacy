<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AIRequestLog;
use Illuminate\Support\Facades\DB;

/**
 * AI Metrics Controller - provides usage and performance metrics.
 *
 * @package App\Http\Controllers\Api
 */
class MetricsController extends Controller
{
    public function metrics()
    {
        try {
            $totalRequests = AIRequestLog::count();
            $successfulRequests = AIRequestLog::where('success', true)->count();
            $successRate = $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 2) : 0;
            
            $avgDuration = AIRequestLog::where('success', true)->avg('duration_ms') ?? 0;

            $byService = AIRequestLog::select('service_type', DB::raw('COUNT(*) as count'), DB::raw('AVG(duration_ms) as avg_duration'))
                ->groupBy('service_type')
                ->get();

            return response()->json([
                'total_requests' => $totalRequests,
                'success_rate' => $successRate,
                'avg_duration_ms' => round($avgDuration, 2),
                'by_service' => $byService,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'total_requests' => 0,
                'success_rate' => 0,
                'avg_duration_ms' => 0,
                'by_service' => [],
                'error' => 'Metrics table may not exist yet'
            ]);
        }
    }

    public function failoverStats()
    {
        try {
            $total = AIRequestLog::count();
            $failovers = AIRequestLog::where('failover_attempt', '>', 1)->count();
            $failoverRate = $total > 0 ? round(($failovers / $total) * 100, 2) : 0;

            $byService = AIRequestLog::select(
                'service_type',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN failover_attempt > 1 THEN 1 ELSE 0 END) as failovers')
            )->groupBy('service_type')->get();

            return response()->json([
                'failover_rate' => $failoverRate,
                'total_requests' => $total,
                'failover_count' => $failovers,
                'by_service' => $byService,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'failover_rate' => 0,
                'by_service' => [],
                'error' => 'Metrics table may not exist yet'
            ]);
        }
    }
}
