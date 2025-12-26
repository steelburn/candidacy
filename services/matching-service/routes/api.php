<?php

use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MatchController;
Route::get('/candidates/{id}/matches', [MatchController::class, 'matchCandidateToVacancies']);
Route::get('/vacancies/{id}/matches', [MatchController::class, 'matchVacancyToCandidates']);
Route::post('/matches/vacancy/{vacancyId}', [MatchController::class, 'matchVacancyToCandidates']);
Route::post('/matches/apply', [MatchController::class, 'apply']);
Route::post('/matches/{candidateId}/{vacancyId}/questions', [MatchController::class, 'generateQuestions']);
Route::post('/matches/{candidateId}/{vacancyId}/questions/{questionIndex}/discussion', [MatchController::class, 'saveDiscussion']);
Route::post('/matches/{candidateId}/{vacancyId}/dismiss', [MatchController::class, 'dismissMatch']);
Route::post('/matches/{candidateId}/{vacancyId}/restore', [MatchController::class, 'restoreMatch']);
Route::get('/matches', [MatchController::class, 'getMatches']);
Route::get('/matches/jobs/{id}', [MatchController::class, 'getJobStatus']);
Route::delete('/matches/clear', [MatchController::class, 'clearMatches']);
Route::get('/health', [HealthController::class, 'check']);
    return response()->json(['status' => 'ok', 'service' => 'matching-service']);
