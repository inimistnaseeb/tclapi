<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api/v1',
], function () {
    Route::middleware(['auth:api', 'verified'])->group(function () {
        Route::get('/user', [App\Http\Controllers\API\UserController::class, 'index']);
        Route::post('/user', [App\Http\Controllers\API\UserController::class, 'store']);
        Route::get('/user/{user}', [App\Http\Controllers\API\UserController::class, 'show']);
        Route::put('/user/{user}', [App\Http\Controllers\API\UserController::class, 'update']);
        Route::delete('/user/{user}', [App\Http\Controllers\API\UserController::class, 'delete']);
    });
    Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login']);
    Route::post('/signup', [App\Http\Controllers\API\AuthController::class, 'register']);
    Route::post('/register', [App\Http\Controllers\API\AuthController::class, 'register']);
    Route::get('/test', function(){
        return "TEST";
    });

});
