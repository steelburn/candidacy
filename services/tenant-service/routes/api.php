<?php

use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\TenantMemberController;
use App\Http\Controllers\Api\TenantInvitationController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Service API Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', [HealthController::class, 'check']);
Route::get('/tenants/health', [HealthController::class, 'check']);

// Public invitation routes (no auth required)
Route::prefix('invitations')->group(function () {
    Route::get('/{token}', [TenantInvitationController::class, 'show']);
    Route::post('/{token}/accept', [TenantInvitationController::class, 'accept']);
});

// Protected routes (require authentication via gateway)
Route::middleware('api')->group(function () {
    // Tenant CRUD
    Route::get('/tenants', [TenantController::class, 'index']);
    Route::post('/tenants', [TenantController::class, 'store']);
    Route::get('/tenants/{uuid}', [TenantController::class, 'show']);
    Route::put('/tenants/{uuid}', [TenantController::class, 'update']);
    Route::delete('/tenants/{uuid}', [TenantController::class, 'destroy']);
    
    // Tenant switching
    Route::post('/tenants/{uuid}/switch', [TenantController::class, 'switchTenant']);
    
    // Tenant members
    Route::get('/tenants/{uuid}/members', [TenantMemberController::class, 'index']);
    Route::post('/tenants/{uuid}/members', [TenantMemberController::class, 'store']);
    Route::put('/tenants/{uuid}/members/{member}', [TenantMemberController::class, 'update']);
    Route::delete('/tenants/{uuid}/members/{member}', [TenantMemberController::class, 'destroy']);
    Route::post('/tenants/{uuid}/leave', [TenantMemberController::class, 'leave']);
    
    // Tenant invitations
    Route::get('/tenants/{uuid}/invitations', [TenantInvitationController::class, 'index']);
    Route::post('/tenants/{uuid}/invitations', [TenantInvitationController::class, 'store']);
    Route::delete('/tenants/{uuid}/invitations/{invitation}', [TenantInvitationController::class, 'destroy']);
});
