<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InterviewController;

Route::apiResource('interviews', InterviewController::class);
Route::post('/interviews/{id}/feedback', [InterviewController::class, 'addFeedback']);
Route::get('/interviews/upcoming/all', [InterviewController::class, 'upcoming']);
Route::get('/interviews/metrics/stats', [InterviewController::class, 'metrics']);

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'interview-service']);
});
