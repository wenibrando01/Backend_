<?php

namespace Database\Factories;

use App\Models\SchoolDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolDay>
 */
class SchoolDayFactory extends Factory
{
    protected $model = SchoolDay::class;

    public function definition(): array
    {
        return [
            'date' => $this->faker->unique()->date(),
            'attendance_rate' => $this->faker->numberBetween(70, 100),
            'is_holiday' => $this->faker->boolean(15),
        ];
    }
}

