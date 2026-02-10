<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    // Home page
    public function home()
    {
        $categories = Category::all();
        return view('welcome', compact('categories'));
    }

    // /wenibrando page
    public function wenibrando()
    {
        $categories = Category::all();
        return view('wenibrando', compact('categories'));
    }

    // Create 5 categories and redirect
    public function createCategories()
    {
        $categories = ['Electronics', 'Books', 'Clothing', 'Toys', 'Furniture'];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        return redirect('/wenibrando');
    }
}
