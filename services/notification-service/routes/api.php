<?php

use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;

// Health check - no auth required
Route::get('/health', [HealthController::class, 'check']);
Route::get('/notifications/health', [HealthController::class, 'check']);

// Tenant-scoped routes
Route::middleware(['tenant', 'require.tenant'])->group(function () {
    // Notification sending
    Route::post('/notifications', [NotificationController::class, 'send']);
    Route::post('/notifications/bulk', [NotificationController::class, 'sendBulk']);

    // Specialized notification endpoints
    Route::post('/notifications/interview-scheduled', [NotificationController::class, 'sendInterviewScheduled']);
    Route::post('/notifications/offer-sent', [NotificationController::class, 'sendOfferSent']);

    // Template management
    Route::get('/notification/templates', [NotificationController::class, 'templates']);
    Route::post('/notification/templates', [NotificationController::class, 'storeTemplate']);
    Route::put('/notification/templates/{id}', [NotificationController::class, 'updateTemplate']);

    // Notification logs
    Route::get('/notifications/logs', [NotificationController::class, 'logs']);
    Route::get('/notifications/logs/{id}', [NotificationController::class, 'showLog']);
    Route::post('/notifications/logs/{id}/retry', [NotificationController::class, 'retry']);
});
