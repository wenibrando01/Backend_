<?php

use Illuminate\Support\Facades\Route;
use App\Models\Category;

// Existing home route
Route::get('/', function () {
    $categories = Category::all();
    return view('welcome', compact('categories'));
});

// New route /wenibrando
Route::get('/wenibrando', function () {
    $categories = Category::all();
    return view('wenibrando', compact('categories')); // use new view
});

// Route to create 5 categories
Route::get('/create-categories', function () {
    $categories = ['Electronics', 'Books', 'Clothing', 'Toys', 'Furniture'];

    foreach ($categories as $name) {
        Category::firstOrCreate(['name' => $name]);
    }

    return redirect('/wenibrando'); // redirect to new page instead of /
});
