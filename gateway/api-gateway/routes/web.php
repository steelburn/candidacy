<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ApiDocController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// API Documentation Routes
Route::get('/api/documentation', [ApiDocController::class, 'renderDocs'])->name('api.docs');
Route::get('/api/documentation/spec', [ApiDocController::class, 'getOpenApiSpec'])->name('api.docs.spec');

// Proxy storage requests to candidate-service for CV downloads
Route::get('/storage/{path}', function ($path) {
    try {
        // Proxy the request to candidate-service
        $targetUrl = "http://candidate-service:8080/storage/{$path}";
        
        Log::info("Proxying storage request", ['url' => $targetUrl]);
        
        $response = Http::timeout(30)->get($targetUrl);
        
        if (!$response->successful()) {
            return response('File not found', 404);
        }
        
        // Get content type from response or guess from extension
        $contentType = $response->header('Content-Type') ?? 'application/octet-stream';
        
        return response($response->body(), 200)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', 'inline; filename="' . basename($path) . '"');
            
    } catch (\Exception $e) {
        Log::error("Storage proxy error: " . $e->getMessage());
        return response('File not found', 404);
    }
})->where('path', '.*');
