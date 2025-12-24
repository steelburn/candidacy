<?php

use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportController;

Route::get('/reports/candidates', [ReportController::class, 'candidateMetrics']);
Route::get('/reports/vacancies', [ReportController::class, 'vacancyMetrics']);
Route::get('/reports/pipeline', [ReportController::class, 'hiringPipeline']);
Route::get('/reports/performance', [ReportController::class, 'performance']);
Route::get('/reports/dashboard', function() {
    // Dashboard aggregates all metrics
    $candidateResponse = app(ReportController::class)->candidateMetrics();
    $vacancyResponse = app(ReportController::class)->vacancyMetrics();
    $pipelineResponse = app(ReportController::class)->hiringPipeline();
    $performanceResponse = app(ReportController::class)->performance();
    
    return response()->json([
        'candidates' => json_decode($candidateResponse->getContent(), true),
        'vacancies' => json_decode($vacancyResponse->getContent(), true),
        'pipeline' => json_decode($pipelineResponse->getContent(), true),
        'performance' => json_decode($performanceResponse->getContent(), true),
    ]);
});

Route::get('/health', [HealthController::class, 'check']);
