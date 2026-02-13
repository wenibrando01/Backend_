<?php

use Illuminate\Support\Facades\Route;
use App\Models\Category;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;


Route::get('/', function () {
    $categories = Category::orderByDesc('id')->take(5)->get();
    return view('welcome', compact('categories'));
});


Route::get('/wenibrando', function () {
    $categories = Category::orderByDesc('id')->take(5)->get();
    return view('wenibrando', compact('categories'));
});


Route::get('/create-categories', function () {
    $categories = ['Electronics', 'Books', 'Clothing', 'Toys', 'Furniture'];

    foreach ($categories as $name) {
        Category::firstOrCreate(['title' => $name]);
    }

    return redirect('/wenibrando'); 
});


Route::resource('posts', PostController::class);

// Create a category via simple POST form
Route::post('/categories', function (Request $request) {
    $data = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
    ]);

    \App\Models\Category::create($data);

    return redirect('/')->with('status', 'Category added');
});
