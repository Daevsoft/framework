<?php

declare(strict_types=1);

use App\Support\Route;

// User domain routes
Route::prefix('/users', function() {
    Route::get('', 'User\Http\Controllers\UserController@index');
    Route::post('', 'User\Http\Controllers\UserController@store');
    Route::get('/{id}', 'User\Http\Controllers\UserController@show');
    Route::put('/{id}', 'User\Http\Controllers\UserController@update');
    Route::delete('/{id}', 'User\Http\Controllers\UserController@destroy');
});

// Definisi route khusus domain User.


