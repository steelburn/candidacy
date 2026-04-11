<?php

use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OfferController;

// Health check - no auth required
Route::get('/offers/health', [HealthController::class, 'check']);
Route::get('/health', [HealthController::class, 'check']);

// Tenant-scoped routes
Route::middleware(['tenant', 'require.tenant'])->group(function () {
    Route::apiResource('offers', OfferController::class);
    Route::post('/offers/{id}/respond', [OfferController::class, 'respond']);
    Route::get('/offers/metrics/stats', [OfferController::class, 'metrics']);
});
