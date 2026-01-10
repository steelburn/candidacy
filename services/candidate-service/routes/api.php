<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Log;

Log::info('Candidate Service: Request hit api.php', [
    'uri' => request()->getRequestUri(),
    'method' => request()->method()
]);

Route::get('/candidates/health', [HealthController::class, 'check']);

// Candidate routes
Route::post('/candidates/parse-cv', [CandidateController::class, 'parseCv']);
Route::get('/candidates/cv-parsing/{id}/status', [CandidateController::class, 'getCvParsingStatus']);
Route::get('/candidates/cv-parsing/{id}/result', [CandidateController::class, 'getCvParsingResult']);
Route::post('/candidates/bulk-upload', [CandidateController::class, 'bulkUploadResumes']);

// Admin CV Jobs Management
Route::get('/candidates/cv-jobs', [CandidateController::class, 'listCvJobs']);
Route::post('/candidates/cv-jobs/{id}/retry', [CandidateController::class, 'retryCvJob']);
Route::delete('/candidates/cv-jobs/{id}', [CandidateController::class, 'deleteCvJob']);
Route::get('/candidates/{id}/parsing-details', [CandidateController::class, 'getParsingDetails']);

Route::apiResource('candidates', CandidateController::class);
Route::post('/candidates/{id}/cv', [CandidateController::class, 'uploadCvFile']);
Route::get('/candidates/{id}/cv', [CandidateController::class, 'getCv']);
Route::get('/candidates/{id}/cv/download', [CandidateController::class, 'downloadCv']);
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
Route::get('/health', [HealthController::class, 'check']);

