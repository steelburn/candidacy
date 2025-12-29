<?php

use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AiController;
// AI endpoints
Route::post('/parse-cv', [AiController::class, 'parseCv']);
Route::post('/generate-jd', [AiController::class, 'generateJobDescription']);
Route::post('/match', [AiController::class, 'matchCandidateToVacancy']);
Route::post('/generate-questions', [AiController::class, 'generateInterviewQuestions']);
Route::post('/generate-questions-screening', [AiController::class, 'generateScreeningQuestions']);
Route::post('/discuss-question', [AiController::class, 'discussQuestion']);
// Health check
Route::get('/health', [HealthController::class, 'check']);

// Metrics
use App\Http\Controllers\Api\MetricsController;
Route::get('/metrics', [MetricsController::class, 'metrics']);
Route::get('/failover-stats', [MetricsController::class, 'failoverStats']);

// Provider management
use App\Http\Controllers\Api\ProvidersController;
Route::get('/providers', [ProvidersController::class, 'index']);
Route::post('/providers/models', [ProvidersController::class, 'listModels']);
Route::post('/providers', [ProvidersController::class, 'store']);
Route::put('/providers/{id}', [ProvidersController::class, 'update']);
Route::delete('/providers/{id}', [ProvidersController::class, 'destroy']);
Route::post('/providers/chains', [ProvidersController::class, 'updateChains']);

