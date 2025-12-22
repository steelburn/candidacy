<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CandidateController;

// Candidate routes
Route::post('/candidates/parse-cv', [CandidateController::class, 'parseCv']);
Route::post('/candidates/bulk-upload', [CandidateController::class, 'bulkUploadResumes']);
Route::apiResource('candidates', CandidateController::class);
Route::post('/candidates/{id}/cv', [CandidateController::class, 'uploadCvFile']);
Route::get('/candidates/{id}/cv', [CandidateController::class, 'getCv']);
Route::get('/candidates/{id}/cv/view', [CandidateController::class, 'downloadCv']);
Route::get('/candidates/metrics/stats', [CandidateController::class, 'metrics']);

// Applicant Portal Routes
Route::post('/candidates/{id}/generate-token', [CandidateController::class, 'generateToken']);
Route::get('/portal/validate-token/{token}', [CandidateController::class, 'validateToken']);
Route::post('/portal/submit-answers/{token}', [CandidateController::class, 'submitAnswers']);
Route::post('/portal/login', [CandidateController::class, 'login']);
Route::post('/portal/generate-pin', [CandidateController::class, 'generatePin']);
Route::get('/portal/dashboard', [CandidateController::class, 'getPortalData']);
Route::get('/candidates/jobs/{id}', [CandidateController::class, 'getJobStatus']);

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'candidate-service']);
});
