<?php

use App\Http\Controllers\Api\AutController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/token-test', function () {
    $user = \App\Models\User::first();

    return $user->createToken('test')->plainTextToken;
});

Route::post('/login', [AutController::class, 'login']);
Route::post('/register', [AutController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function () {
        return auth()->user();
    });

    Route::post('/logout', [AutController::class, 'logout']);

    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
});