<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
   
    public function run(): void
    {
        $categories = ['Electronics', 'Books', 'Clothing', 'Toys', 'Furniture'];

        foreach ($categories as $title) {
            \App\Models\Category::firstOrCreate(['title' => $title]);
        }
    }
}
