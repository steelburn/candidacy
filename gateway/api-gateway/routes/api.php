<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\GatewayController;

// JWT authenticated user endpoint
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'service' => 'api-gateway',
        'timestamp' => now()->toIso8601String()
    ]);
});

// Catch-all route for API Gateway
// Note: Auth is handled internally by GatewayController using JWT
Route::any('/{any}', [GatewayController::class, 'handle'])->where('any', '.*');
