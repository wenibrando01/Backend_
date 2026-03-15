<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Course;

class StudentSeeder extends Seeder
{
    public function run()
    {
        if (! Course::query()->exists()) {
            Course::factory()->count(20)->create();
        }

        Student::factory()->count(500)->create();
    }
}