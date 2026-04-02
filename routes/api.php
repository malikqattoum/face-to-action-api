<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CallSessionController;
use App\Http\Controllers\LogController;
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
});
