<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\SchoolDay;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function stats()
    {
        $totalStudents = Student::query()->count();
        $totalCourses = Course::query()->count();
        $totalSchoolDays = SchoolDay::query()->count();
        $activeUsers = User::query()->whereIn('role', ['admin', 'student'])->count();

        return response()->json([
            'total_students' => $totalStudents,
            'total_courses' => $totalCourses,
            'total_school_days' => $totalSchoolDays,
            'active_users' => $activeUsers,
        ]);
    }

    public function recentActivity(Request $request)
    {
        $limit = (int) $request->get('limit', 10);
        $limit = min(max($limit, 1), 50);

        $students = Student::query()->latest('updated_at')->limit($limit)->get(['id', 'name', 'email', 'updated_at']);
        $activities = $students->map(fn ($s) => [
            'type' => 'student',
            'id' => $s->id,
            'description' => "Student {$s->name} updated",
            'date' => $s->updated_at?->toISOString(),
        ]);

        return response()->json(['data' => $activities->values()->all()]);
    }
}
