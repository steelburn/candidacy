<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ParserController;

Route::post('/parse', [ParserController::class, 'parse']);
Route::get('/parse/{id}/status', [ParserController::class, 'status']);
Route::get('/parse/{id}/result', [ParserController::class, 'result']);
Route::get('/health', fn() => response()->json(['status' => 'healthy', 'service' => 'document-parser']));
