<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $department = $this->faker->randomElement([
            'Science',
            'Engineering',
            'Business',
            'Arts',
            'Education',
            'Health',
        ]);

        $code = strtoupper($this->faker->unique()->regexify('[A-Z]{2}[0-9]{3}'));

        return [
            'course_code' => $code,
            'code' => $code,
            'course_name' => $this->faker->unique()->words(3, true),
            'title' => $this->faker->sentence(3),
            'department' => $department,
            'description' => $this->faker->optional(0.7)->sentence(),
            'status' => 'active',
           'credits' => $this->faker->numberBetween(1, 4),
        ];
    }
}

