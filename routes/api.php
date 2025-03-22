<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ImageController;

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

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Image routes
    Route::get('/images', [ImageController::class, 'index']);
    Route::get('/images/search', [ImageController::class, 'search']);
    Route::get('/images/photographers', [ImageController::class, 'getPhotographers']);
    Route::get('/images/tags', [ImageController::class, 'getTags']);
    Route::get('/images/suggestions', [ImageController::class, 'getSuggestions']);
});
