<?php

use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;
Route::post('/notifications', [NotificationController::class, 'send']);
Route::post('/notifications/bulk', [NotificationController::class, 'sendBulk']);
Route::get('/notification/templates', [NotificationController::class, 'templates']);
Route::get('/health', [HealthController::class, 'check']);
    return response()->json(['status' => 'ok', 'service' => 'notification-service']);
