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

