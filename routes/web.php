<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return redirect()->route('posts.index');
});

Route::resource('posts', PostController::class);

// Create a category via simple POST form
Route::post('/categories', function (Request $request) {
    $data = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
    ]);

    \App\Models\Category::create($data);

    return redirect()->route('posts.index')->with('status', 'Category added');
});
