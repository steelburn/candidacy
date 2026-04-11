<?php

use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OnboardingController;

// Health check - no auth required
Route::get('/onboarding/health', [HealthController::class, 'check']);
Route::get('/health', [HealthController::class, 'check']);

// Tenant-scoped routes
Route::middleware(['tenant', 'require.tenant'])->group(function () {
    Route::get('/candidates/{candidateId}/onboarding', [OnboardingController::class, 'index']);
    Route::post('/onboarding', [OnboardingController::class, 'store']);
    Route::put('/onboarding/{id}', [OnboardingController::class, 'update']);
    Route::post('/onboarding/{id}/complete', [OnboardingController::class, 'markComplete']);
    Route::get('/candidates/{candidateId}/onboarding/progress', [OnboardingController::class, 'progress']);
});
