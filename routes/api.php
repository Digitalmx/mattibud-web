<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\StoreImageController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Store API Routes
Route::apiResource('stores', StoreController::class);

// Location API Routes (Google Places API proxies)
Route::get('/locations/autocomplete', [LocationController::class, 'autocomplete']);
Route::get('/locations/details', [LocationController::class, 'details']);

// Store Images API Routes
Route::post('/stores/{store}/images', [StoreImageController::class, 'uploadImage']);
Route::post('/stores/{store}/pdf', [StoreImageController::class, 'uploadPdf']);
Route::get('/stores/{store}/images', [StoreImageController::class, 'getImages']);
Route::delete('/store-images/{storeImage}', [StoreImageController::class, 'deleteImage']);
Route::put('/store-images/sort', [StoreImageController::class, 'updateSortOrder']);

// API route for creating, updating, and deleting stores can be protected later
// Route::middleware('auth:sanctum')->apiResource('stores', StoreController::class)->except(['index', 'show']);
