<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VacancyController;

Route::apiResource('vacancies', VacancyController::class);
Route::get('/vacancies/metrics/stats', [VacancyController::class, 'metrics']);
Route::post('/vacancies/{id}/generate-description', [VacancyController::class, 'generateDescription']);
Route::post('/vacancies/{id}/questions', [VacancyController::class, 'addQuestion']);
Route::get('/vacancies/{id}/questions', [VacancyController::class, 'getQuestions']);

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'vacancy-service']);
});
