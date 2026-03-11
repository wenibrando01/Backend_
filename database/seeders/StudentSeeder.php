<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            'Computer Science',
            'Information Technology',
            'Business Administration',
            'Engineering',
            'Education',
        ];

        $genders = ['male', 'female', 'other'];

        $students = [];

        for ($i = 0; $i < 500; $i++) {
            $first = fake()->firstName();
            $last = fake()->lastName();
            $email = Str::slug($first.'.'.$last.'.'.Str::random(4)).'@example.com';

            $students[] = [
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'gender' => Arr::random($genders),
                'date_of_birth' => fake()->dateTimeBetween('-25 years', '-16 years')->format('Y-m-d'),
                'department' => Arr::random($departments),
                'year_level' => fake()->numberBetween(1, 4),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Student::insert($students);
    }
}
