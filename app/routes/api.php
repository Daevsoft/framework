<?php

declare(strict_types=1);

use App\Domains\User\Http\Controllers\UserController;
use App\Support\Route;

// API Routes with /api prefix
Route::prefix('/api', function() {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});


