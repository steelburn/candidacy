<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OfferController;

Route::apiResource('offers', OfferController::class);
Route::post('/offers/{id}/respond', [OfferController::class, 'respond']);
Route::get('/offers/metrics/stats', [OfferController::class, 'metrics']);

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'offer-service']);
});
