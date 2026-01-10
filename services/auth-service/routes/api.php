<?php

use Illuminate\Http\Request;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// First-time setup routes (only work when no users exist)
Route::get('/auth/setup/check', [AuthController::class, 'setupCheck']);
Route::post('/auth/setup/create-admin', [AuthController::class, 'createFirstAdmin']);

// Protected routes - using auth:api for JWT authentication
Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    
    // User management
    Route::apiResource('users', UserController::class);
    
    // Role management
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{id}', [RoleController::class, 'show']);
    Route::post('/users/{userId}/roles', [RoleController::class, 'assignRole']);
    Route::delete('/users/{userId}/roles/{roleId}', [RoleController::class, 'removeRole']);
});

// Health check
Route::get('/health', [HealthController::class, 'check']);
Route::get('/auth/health', [HealthController::class, 'check']);
