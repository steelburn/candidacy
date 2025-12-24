<?php

use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'check']);

Route::middleware('api')->group(function () {
    // Settings routes
    Route::get('/settings', [SettingController::class, 'index']);
    Route::put('/settings', [SettingController::class, 'update']);
    Route::get('/settings/{key}', [SettingController::class, 'show']);
    Route::put('/settings/{key}', [SettingController::class, 'updateSingle']);
    Route::get('/settings/category/{category}', [SettingController::class, 'getByCategory']);
    
    // System health
    Route::get('/system-health', [SettingController::class, 'systemHealth']);
});
