<?php

declare(strict_types=1);

use App\Http\Controllers\HelloController;
use App\Support\Route;

// Home route
Route::get('/', [HelloController::class, 'index']);
Route::get('/health', [HelloController::class, 'health']);

// API Routes
Route::get('/api/ping', function() {
    return response()->json(['message' => 'pong']);
});

// Example routes for User domain
Route::get('/users', [HelloController::class, 'users']);
Route::post('/users', [HelloController::class, 'createUser']);
Route::get('/users/{id}', [HelloController::class, 'showUser']);
Route::put('/users/{id}', [HelloController::class, 'updateUser']);
Route::delete('/users/{id}', [HelloController::class, 'deleteUser']);



