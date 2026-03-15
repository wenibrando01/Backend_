<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        $gender = $this->faker->randomElement(['male', 'female', 'other']);
        $first = $this->faker->firstName();
        $last = $this->faker->lastName();
        $dateOfBirth = $this->faker->dateTimeBetween('-22 years', '-10 years');
        $department = $this->faker->randomElement([
            'Science', 'Engineering', 'Business', 'Arts', 'Education', 'Health',
        ]);

        return [
            'name' => trim($first . ' ' . $last),
            'first_name' => $first,
            'last_name' => $last,
            'email' => $this->faker->unique()->safeEmail(),
            'age' => Carbon::instance($dateOfBirth)->age,
            'gender' => $gender,
            'course_id' => Course::query()->inRandomOrder()->value('id') ?? Course::factory(),
            'date_of_birth' => $dateOfBirth->format('Y-m-d'),
            'department' => $department,
            'year_level' => $this->faker->numberBetween(1, 5),
            'status' => 'active',
            'created_at' => $this->faker->dateTimeBetween('-12 months', 'now'),
            'updated_at' => now(),
        ];
    }
}

