<?php
use App\Http\Controllers\Api\AutController;

Route::get ('/token-test', function () {
    $user = \app\Models\User::first();
    return $user->createToken('test')->plainTextToken;
});
Route::post('/login', [AutController::class, 'login']);