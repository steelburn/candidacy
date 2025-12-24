<?php

use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OnboardingController;
Route::get('/candidates/{candidateId}/onboarding', [OnboardingController::class, 'index']);
Route::post('/onboarding', [OnboardingController::class, 'store']);
Route::put('/onboarding/{id}', [OnboardingController::class, 'update']);
Route::post('/onboarding/{id}/complete', [OnboardingController::class, 'markComplete']);
Route::get('/candidates/{candidateId}/onboarding/progress', [OnboardingController::class, 'progress']);
Route::get('/health', [HealthController::class, 'check']);
    return response()->json(['status' => 'ok', 'service' => 'onboarding-service']);
