<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\SchoolDay;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary()
    {
        $studentsCount = Student::query()->count();
        $coursesCount = Course::query()->count();

        $attendanceSeries = SchoolDay::query()
            ->orderBy('date')
            ->limit(60)
            ->get(['date', 'attendance_rate', 'is_holiday']);

        $avgAttendance = (int) round(
            SchoolDay::query()
                ->where('is_holiday', false)
                ->avg('attendance_rate') ?? 0
        );

        $holidaysCount = SchoolDay::query()->where('is_holiday', true)->count();

        $courseDistribution = Course::query()
            ->withCount('students')
            ->orderByDesc('students_count')
            ->limit(20)
            ->get()
            ->map(fn ($c) => [
                'course_name' => $c->course_name,
                'department' => $c->department,
                'students' => $c->students_count,
            ]);

        $driver = DB::connection()->getDriverName();
        $monthExpr = match ($driver) {
            'pgsql' => "TO_CHAR(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $monthlyEnrollmentTrends = Student::query()
            ->selectRaw("$monthExpr as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'cards' => [
                'students' => $studentsCount,
                'courses' => $coursesCount,
                'avgAttendance' => $avgAttendance,
                'holidays' => $holidaysCount,
            ],
            'charts' => [
                'monthlyEnrollmentTrends' => $monthlyEnrollmentTrends,
                'courseDistribution' => $courseDistribution,
                'attendance' => $attendanceSeries,
            ],
        ]);
    }
}
