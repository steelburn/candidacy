<?php

use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'check']);
Route::get('/admin/health', [HealthController::class, 'check']);

Route::middleware('api')->group(function () {
    // Settings routes
    Route::get('/settings', [SettingController::class, 'index']);
    Route::get('/settings/detailed', [SettingController::class, 'getAllDetailed']);
    Route::get('/settings/export', [SettingController::class, 'export']);
    Route::post('/settings/import', [SettingController::class, 'import']);
    Route::put('/settings', [SettingController::class, 'update']);
    Route::get('/settings/category/{category}', [SettingController::class, 'getByCategory']);
    Route::get('/settings/scope/{scope}', [SettingController::class, 'getByScope']);
    Route::get('/settings/{key}', [SettingController::class, 'show']);
    Route::get('/settings/{key}/history', [SettingController::class, 'history']);
    Route::put('/settings/{key}', [SettingController::class, 'updateSingle']);
    
    // System health
    Route::get('/system-health', [SettingController::class, 'systemHealth']);
});
