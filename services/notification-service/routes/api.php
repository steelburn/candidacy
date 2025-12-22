<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;

Route::post('/notifications', [NotificationController::class, 'send']);
Route::post('/notifications/bulk', [NotificationController::class, 'sendBulk']);
Route::get('/notification/templates', [NotificationController::class, 'templates']);

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'notification-service']);
});
