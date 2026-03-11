<?php

use App\Http\Controllers\Api\AutController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SchoolDayController;
use App\Http\Controllers\Api\StudentController;
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

    Route::get('/dashboard', DashboardController::class);

    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{student}', [StudentController::class, 'show']);
    Route::delete('/students/{student}', [StudentController::class, 'destroy']);

    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);

    Route::get('/school-days', [SchoolDayController::class, 'index']);
    Route::get('/school-days/{schoolDay}', [SchoolDayController::class, 'show']);

    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
});