<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    private const SEEDED_PRIORITY_CODES = [
        'ABEL',
        'BEED',
        'BSEDENG',
        'BSEDMATH',
        'BPE',
        'BSA',
        'BSCpE',
        'BSCS',
        'BSCRIM',
        'BSEE',
        'BSECE',
        'BSFM',
        'BSHM',
        'BSHRM',
        'BSIT',
        'BSIA',
        'BSMA',
        'BSMM',
        'BSP',
        'BSTM',
    ];

    public function index(Request $request)
    {
        $query = Course::query()->orderBy('course_name');

        $search = $request->string('search')->trim();
        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search) {
                $q->where('course_name', 'like', '%' . $search . '%')
                    ->orWhere('course_code', 'like', '%' . $search . '%')
                    ->orWhere('department', 'like', '%' . $search . '%');
            });
        }

        $perPage = (int) $request->get('per_page', 0);
        if ($perPage > 0) {
            $perPage = min($perPage, 100);
            $courses = $query->paginate($perPage);
            return CourseResource::collection($courses);
        }
        $courses = $query->get();
        return response()->json(CourseResource::collection($courses));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_name' => ['required', 'string', 'max:255'],
            'course_code' => ['nullable', 'string', 'max:64'],
            'department' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $validated['status'] = $validated['status'] ?? 'active';
        $course = Course::query()->create($validated);

        return response()->json(new CourseResource($course), 201);
    }

    public function show(string $id)
    {
        $course = Course::query()->withCount('students')->findOrFail($id);
        return response()->json(new CourseResource($course));
    }

    public function update(Request $request, string $id)
    {
        $course = Course::query()->findOrFail($id);

        $validated = $request->validate([
            'course_name' => ['sometimes', 'string', 'max:255'],
            'course_code' => ['nullable', 'string', 'max:64'],
            'department' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $course->update($validated);

        return response()->json(new CourseResource($course));
    }

    public function destroy(string $id)
    {
        $course = Course::query()->findOrFail($id);
        $course->delete();
        return response()->json(['message' => 'Course deleted'], 200);
    }

    public function distribution()
    {
        $seededRank = array_flip(self::SEEDED_PRIORITY_CODES);

        $distribution = Course::query()
            ->withCount('students')
            ->addSelect([
                'enrolled_students_count' => Enrollment::query()
                    ->selectRaw('COUNT(DISTINCT student_id)')
                    ->whereColumn('enrollments.course_id', 'courses.id'),
            ])
            ->get();

        $unassignedCount = Student::query()->whereNull('course_id')->count();

        $data = $distribution
            ->map(function ($course) use ($seededRank) {
                $studentsCount = (int) ($course->students_count ?? 0);
                $enrolledCount = (int) ($course->enrolled_students_count ?? 0);
                $effectiveCount = max($studentsCount, $enrolledCount);
                $courseCode = (string) ($course->course_code ?? '');

                return [
                    'id' => $course->id,
                    'course_name' => $course->course_name,
                    'course_code' => $course->course_code,
                    'department' => $course->department,
                    'description' => $course->description,
                    'status' => $course->status,
                    'students_count' => $effectiveCount,
                    'seeded_priority' => array_key_exists($courseCode, $seededRank)
                        ? (int) $seededRank[$courseCode]
                        : 9999,
                ];
            })
            ->sortBy([
                ['seeded_priority', 'asc'],
                ['students_count', 'desc'],
                ['course_name', 'asc'],
            ])
            ->values()
            ->all();

        if ($unassignedCount > 0) {
            $data[] = [
                'id' => null,
                'course_name' => 'Unassigned',
                'course_code' => 'N/A',
                'department' => 'N/A',
                'description' => null,
                'status' => 'active',
                'students_count' => $unassignedCount,
                'seeded_priority' => 10000,
            ];
        }

        return response()->json([
            'data' => $data,
        ]);
    }
}
