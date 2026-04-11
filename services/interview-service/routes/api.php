<?php

use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InterviewController;

// Health check - no auth required
Route::get('/interviews/health', [HealthController::class, 'check']);
Route::get('/health', [HealthController::class, 'check']);

// Tenant-scoped routes
Route::middleware(['tenant', 'require.tenant'])->group(function () {
    Route::apiResource('interviews', InterviewController::class);
    Route::post('/interviews/{id}/feedback', [InterviewController::class, 'addFeedback']);
    Route::get('/interviews/upcoming/all', [InterviewController::class, 'upcoming']);
    Route::get('/interviews/metrics/stats', [InterviewController::class, 'metrics']);
});
