<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolDay;
use Carbon\Carbon;

class SchoolDaySeeder extends Seeder
{
    public function run()
    {
        $eventTemplates = [
            'Orientation Program',
            'Department Meeting',
            'Laboratory Session',
            'Midterm Examination',
            'Seminar Workshop',
            'Sports Activity',
            'Club Event',
            'Faculty Development Day',
            'Research Presentation',
            'Final Examination',
        ];

        $year = (int) now()->format('Y');
        $start = Carbon::createFromDate($year, 1, 1);
        $end = Carbon::createFromDate($year, 12, 31);

        for ($date = $start; $date->lte($end); $date->addDay()) {
            $isHoliday = $date->isWeekend();
            $attendance = $isHoliday ? 0 : rand(75, 100);
            $description = null;

            if ($isHoliday) {
                $description = 'Weekend / holiday';
            } elseif (rand(1, 100) <= 35) {
                $description = $eventTemplates[array_rand($eventTemplates)];
            }

            SchoolDay::updateOrCreate([
                'date' => $date->format('Y-m-d'),
            ], [
                'attendance_rate' => $attendance,
                'is_holiday' => $isHoliday,
                'description' => $description,
            ]);
        }
    }
}