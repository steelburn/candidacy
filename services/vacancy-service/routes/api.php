<?php

use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VacancyController;

// Health check - no auth required
Route::get('/vacancies/health', [HealthController::class, 'check']);
Route::get('/health', [HealthController::class, 'check']);

// Tenant-scoped routes
Route::middleware(['auth:api', 'tenant', 'require.tenant'])->group(function () {
    Route::apiResource('vacancies', VacancyController::class);
    Route::get('/vacancies/metrics/stats', [VacancyController::class, 'metrics']);
    Route::post('/vacancies/{id}/generate-description', [VacancyController::class, 'generateDescription']);
    Route::post('/vacancies/{id}/questions', [VacancyController::class, 'addQuestion']);
    Route::get('/vacancies/{id}/questions', [VacancyController::class, 'getQuestions']);
    Route::put('/vacancies/{id}/questions/{questionId}', [VacancyController::class, 'updateQuestion']);
    Route::delete('/vacancies/{id}/questions/{questionId}', [VacancyController::class, 'deleteQuestion']);
});
