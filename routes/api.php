<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CallSessionController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\LogPhotoController;
use App\Http\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

// Auth routes (public)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected auth routes
Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('logs', LogController::class);
    Route::apiResource('calls', CallSessionController::class);
    Route::post('calls/{id}/memo', [CallSessionController::class, 'attachMemo']);

    // Photo routes
    Route::get('logs/{logId}/photos', [LogPhotoController::class, 'index']);
    Route::post('logs/{logId}/photos', [LogPhotoController::class, 'store']);
    Route::get('logs/{logId}/photos/{photoId}', [LogPhotoController::class, 'showPhoto']);
    Route::delete('logs/{logId}/photos/{photoId}', [LogPhotoController::class, 'destroy']);

    // Quote PDF route
    Route::get('logs/{id}/quote/pdf', [QuoteController::class, 'generatePdf']);
});
