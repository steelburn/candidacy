<?php

use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportController;

// Health check - no auth required
Route::get('/reports/health', [HealthController::class, 'check']);
Route::get('/health', [HealthController::class, 'check']);

// Tenant-scoped routes
Route::middleware(['auth:api', 'tenant', 'require.tenant'])->group(function () {
    Route::get('/reports/candidates', [ReportController::class, 'candidateMetrics']);
    Route::get('/reports/vacancies', [ReportController::class, 'vacancyMetrics']);
    Route::get('/reports/pipeline', [ReportController::class, 'hiringPipeline']);
    Route::get('/reports/performance', [ReportController::class, 'performance']);
    Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);

    // AI Metrics routes
    Route::get('/reports/ai-metrics', [ReportController::class, 'aiMetrics']);
    Route::get('/reports/ai-failover-stats', [ReportController::class, 'aiFailoverStats']);
});
