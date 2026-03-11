<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\SchoolDay;
use App\Models\Student;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $studentsCount = Student::count();
        $coursesCount = Course::count();

        $today = now()->toDateString();

        $todayAttendance = SchoolDay::where('date', $today)
            ->where('is_school_day', true)
            ->value('attendance_count') ?? 0;

        $totalSchoolDays = SchoolDay::where('is_school_day', true)->count();
        $totalHolidays = SchoolDay::where('type', 'holiday')->count();
        $totalEvents = SchoolDay::where('type', 'event')->count();

        $attendanceTrend = SchoolDay::where('is_school_day', true)
            ->orderBy('date')
            ->limit(30)
            ->get(['date', 'attendance_count']);

        return response()->json([
            'cards' => [
                'students' => $studentsCount,
                'courses' => $coursesCount,
                'todayAttendance' => $todayAttendance,
                'schoolDays' => $totalSchoolDays,
                'holidays' => $totalHolidays,
                'events' => $totalEvents,
            ],
            'attendanceTrend' => $attendanceTrend,
        ]);
    }
}
