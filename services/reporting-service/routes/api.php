<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportController;

Route::get('/reports/candidates', [ReportController::class, 'candidateMetrics']);
Route::get('/reports/vacancies', [ReportController::class, 'vacancyMetrics']);
Route::get('/reports/pipeline', [ReportController::class, 'hiringPipeline']);
Route::get('/reports/performance', [ReportController::class, 'performance']);

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'reporting-service']);
});
