<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\SchoolDaySeeder;
use Database\Seeders\StudentSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            CategorySeeder::class,
            PostSeeder::class,
            StudentSeeder::class,
            CourseSeeder::class,
            SchoolDaySeeder::class,
        ]);
    }
}
