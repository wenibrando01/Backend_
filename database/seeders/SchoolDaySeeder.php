<?php

namespace Database\Seeders;

use App\Models\SchoolDay;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class SchoolDaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate an academic year around the current date
        $start = now()->startOfYear()->addMonths(7)->startOfMonth(); // approx August 1
        $end = (clone $start)->addMonths(10); // ~10 month academic year

        $current = $start->copy();

        $holidays = [
            'New Year Holiday',
            'Independence Day',
            'Founders Day',
            'Midterm Break',
            'End of Term Break',
        ];

        while ($current->lte($end)) {
            $isWeekend = in_array($current->dayOfWeekIso, [6, 7], true);

            $type = 'class';
            $isSchoolDay = ! $isWeekend;
            $description = null;
            $attendance = 0;

            if ($isWeekend) {
                $type = 'holiday';
                $isSchoolDay = false;
                $description = 'Weekend';
            } elseif (fake()->boolean(5)) {
                // 5% chance of special holiday or event
                if (fake()->boolean()) {
                    $type = 'holiday';
                    $isSchoolDay = false;
                    $description = Arr::random($holidays);
                } else {
                    $type = 'event';
                    $description = 'School event';
                }
            }

            if ($isSchoolDay) {
                $attendance = fake()->numberBetween(300, 500);
            }

            SchoolDay::create([
                'date' => $current->toDateString(),
                'type' => $type,
                'is_school_day' => $isSchoolDay,
                'attendance_count' => $attendance,
                'description' => $description,
            ]);

            $current->addDay();
        }
    }
}
