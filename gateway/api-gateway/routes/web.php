<?php

use Illuminate\Support\Facades\Route;
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
