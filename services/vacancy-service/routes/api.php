<?php

use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
Route::get('/vacancies/health', [HealthController::class, 'check']);
use App\Http\Controllers\Api\VacancyController;
Route::apiResource('vacancies', VacancyController::class);
Route::get('/vacancies/metrics/stats', [VacancyController::class, 'metrics']);
Route::post('/vacancies/{id}/generate-description', [VacancyController::class, 'generateDescription']);
Route::post('/vacancies/{id}/questions', [VacancyController::class, 'addQuestion']);
Route::get('/vacancies/{id}/questions', [VacancyController::class, 'getQuestions']);
Route::put('/vacancies/{id}/questions/{questionId}', [VacancyController::class, 'updateQuestion']);
Route::delete('/vacancies/{id}/questions/{questionId}', [VacancyController::class, 'deleteQuestion']);
Route::get('/health', [HealthController::class, 'check']);
    return response()->json(['status' => 'ok', 'service' => 'vacancy-service']);
