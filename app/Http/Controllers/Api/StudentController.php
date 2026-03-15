<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query()->with('course');

        $search = $request->string('search')->trim();
        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search) {
                $searchText = (string) $search;
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhereHas('course', function ($cq) use ($searchText) {
                        $cq->where('course_name', 'like', '%' . $searchText . '%')
                            ->orWhere('course_code', 'like', '%' . $searchText . '%');
                    });

                if (is_numeric($searchText)) {
                    $q->orWhere('id', (int) $searchText);
                }
            });
        }

        $courseId = $request->integer('course_id');
        if ($courseId > 0) {
            $query->where('course_id', $courseId);
        }

        $yearLevel = $request->integer('year_level');
        if ($yearLevel > 0) {
            $query->where('year_level', $yearLevel);
        }

        $status = strtolower(trim((string) $request->input('status', '')));
        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
        $students = $query->latest('id')->paginate($perPage);

        return StudentResource::collection($students);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:students,email'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'department' => ['nullable', 'string', 'max:255'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:10'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $name = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
        $validated['name'] = $name ?: 'Unknown';
        if (! isset($validated['age']) && isset($validated['date_of_birth'])) {
            $validated['age'] = now()->diffInYears($validated['date_of_birth']);
        }
        $validated['age'] = $validated['age'] ?? 18;
        $validated['year_level'] = $validated['year_level'] ?? 1;
        $validated['status'] = $validated['status'] ?? 'active';

        $student = Student::query()->create($validated);

        return response()->json(new StudentResource($student->load('course')), 201);
    }

    public function show(string $id)
    {
        $student = Student::query()->with('course')->findOrFail($id);
        return response()->json(new StudentResource($student));
    }

    public function enrolledCourses(string $id)
    {
        $student = Student::query()->findOrFail($id);

        $rows = Enrollment::query()
            ->with('course')
            ->where('student_id', $student->id)
            ->whereNotNull('subject_name')
            ->whereRaw("TRIM(subject_name) <> ''")
            ->orderByDesc('enrolled_on')
            ->orderByDesc('id')
            ->get();

        $grouped = $rows
            ->filter(fn ($row) => $row->course)
            ->groupBy('course_id')
            ->map(function ($items) {
                $first = $items->first();
                $last = $items->sortByDesc('enrolled_on')->first()?->enrolled_on;
                return [
                    'course_id' => $first->course_id,
                    'course_name' => $first->course?->course_name,
                    'course_code' => $first->course?->course_code,
                    'subjects_count' => $items->count(),
                    'last_enrolled_on' => $last
                        ? (method_exists($last, 'toDateString') ? $last->toDateString() : (string) $last)
                        : null,
                ];
            })
            ->values();

        $subjects = $rows
            ->filter(fn ($row) => $row->course)
            ->map(function ($row) {
                $enrolledOn = $row->enrolled_on;
                return [
                    'id' => $row->id,
                    'course_id' => $row->course_id,
                    'course_name' => $row->course?->course_name,
                    'subject_name' => $row->subject_name,
                    'preferred_session' => $row->preferred_session,
                    'schedule_label' => $row->schedule_label,
                    'enrolled_on' => $enrolledOn
                        ? (method_exists($enrolledOn, 'toDateString') ? $enrolledOn->toDateString() : (string) $enrolledOn)
                        : null,
                ];
            })
            ->values();

        return response()->json([
            'data' => $grouped,
            'courses' => $grouped,
            'subjects' => $subjects,
        ]);
    }

    public function enrolledSubjects(Request $request)
    {
        $query = Enrollment::query()
            ->with(['student.course', 'course'])
            ->whereNotNull('subject_name')
            ->whereRaw("TRIM(subject_name) <> ''");

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('subject_name', 'like', '%' . $search . '%')
                    ->orWhere('preferred_session', 'like', '%' . $search . '%')
                    ->orWhere('schedule_label', 'like', '%' . $search . '%')
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('course', function ($cq) use ($search) {
                        $cq->where('course_name', 'like', '%' . $search . '%')
                            ->orWhere('course_code', 'like', '%' . $search . '%');
                    });

                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search)
                        ->orWhere('student_id', (int) $search);
                }
            });
        }

        $courseId = (int) $request->input('course_id', 0);
        if ($courseId > 0) {
            $query->where('course_id', $courseId);
        }

        $studentId = (int) $request->input('student_id', 0);
        if ($studentId > 0) {
            $query->where('student_id', $studentId);
        }

        $session = trim((string) $request->input('session', ''));
        if ($session !== '') {
            $query->whereRaw('LOWER(preferred_session) = ?', [strtolower($session)]);
        }

        $perPage = min(max((int) $request->input('per_page', 50), 1), 200);
        $rows = $query
            ->orderByDesc('enrolled_on')
            ->orderByDesc('id')
            ->paginate($perPage);

        $rows->setCollection(
            $rows->getCollection()->map(function ($row) {
                $enrolledOn = $row->enrolled_on;
                $studentName = trim(($row->student?->first_name ?? '') . ' ' . ($row->student?->last_name ?? ''));

                return [
                    'id' => $row->id,
                    'student_id' => $row->student_id,
                    'student_name' => $studentName !== '' ? $studentName : ($row->student?->name ?? null),
                    'student_email' => $row->student?->email,
                    'course_id' => $row->course_id,
                    'course_name' => $row->course?->course_name,
                    'course_code' => $row->course?->course_code,
                    'subject_name' => $row->subject_name,
                    'preferred_session' => $row->preferred_session,
                    'schedule_label' => $row->schedule_label,
                    'enrolled_on' => $enrolledOn
                        ? (method_exists($enrolledOn, 'toDateString') ? $enrolledOn->toDateString() : (string) $enrolledOn)
                        : null,
                ];
            })->values()
        );

        return response()->json($rows);
    }

    public function updateEnrolledSubject(Request $request, string $id)
    {
        $enrollment = Enrollment::query()->with(['student', 'course'])->findOrFail($id);

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'subject_name' => ['required', 'string', 'max:255'],
            'preferred_session' => ['nullable', 'string', 'in:morning,afternoon,evening'],
            'schedule_label' => ['nullable', 'string', 'max:255'],
            'enrolled_on' => ['nullable', 'date'],
        ]);

        $normalizedSubject = strtolower(trim((string) ($validated['subject_name'] ?? '')));
        $conflictExists = Enrollment::query()
            ->where('student_id', $enrollment->student_id)
            ->where('course_id', (int) $validated['course_id'])
            ->whereRaw('LOWER(TRIM(subject_name)) = ?', [$normalizedSubject])
            ->where('id', '!=', $enrollment->id)
            ->exists();

        if ($conflictExists) {
            return response()->json([
                'message' => 'This student is already enrolled in that subject for the selected course.',
            ], 422);
        }

        $enrollment->update([
            'course_id' => (int) $validated['course_id'],
            'subject_name' => trim((string) $validated['subject_name']),
            'preferred_session' => $validated['preferred_session'] ?? null,
            'schedule_label' => $validated['schedule_label'] ?? null,
            'enrolled_on' => $validated['enrolled_on'] ?? null,
        ]);

        $enrollment->load(['student', 'course']);
        $studentName = trim(($enrollment->student?->first_name ?? '') . ' ' . ($enrollment->student?->last_name ?? ''));

        return response()->json([
            'message' => 'Enrollment updated successfully.',
            'data' => [
                'id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'student_name' => $studentName !== '' ? $studentName : ($enrollment->student?->name ?? null),
                'student_email' => $enrollment->student?->email,
                'course_id' => $enrollment->course_id,
                'course_name' => $enrollment->course?->course_name,
                'course_code' => $enrollment->course?->course_code,
                'subject_name' => $enrollment->subject_name,
                'preferred_session' => $enrollment->preferred_session,
                'schedule_label' => $enrollment->schedule_label,
                'enrolled_on' => optional($enrollment->enrolled_on)?->toDateString(),
            ],
        ]);
    }

    public function destroyEnrolledSubject(string $id)
    {
        $enrollment = Enrollment::query()->findOrFail($id);
        $enrollment->delete();

        return response()->json([
            'message' => 'Enrollment deleted successfully.',
        ]);
    }

    public function update(Request $request, string $id)
    {
        $student = Student::query()->findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:students,email,' . $student->id],
            'age' => ['sometimes', 'integer', 'min:1', 'max:120'],
            'gender' => ['sometimes', 'string', 'in:male,female,other'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'department' => ['sometimes', 'nullable', 'string', 'max:255'],
            'course_id' => ['sometimes', 'exists:courses,id'],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:10'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        if (isset($validated['first_name']) || isset($validated['last_name'])) {
            $first = $validated['first_name'] ?? $student->first_name ?? '';
            $last = $validated['last_name'] ?? $student->last_name ?? '';
            $validated['name'] = trim($first . ' ' . $last) ?: $student->name;
        }

        if (! isset($validated['age']) && array_key_exists('date_of_birth', $validated) && ! empty($validated['date_of_birth'])) {
            $validated['age'] = now()->diffInYears($validated['date_of_birth']);
        }

        $student->update($validated);

        return response()->json(new StudentResource($student->load('course')));
    }

    public function destroy(string $id)
    {
        $student = Student::findOrFail($id);
        $student->delete();
        return response()->json(['message' => 'Student deleted'], 200);
    }

    public function bulkAssignCourse(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
        ]);

        $updated = Student::query()
            ->whereIn('id', $validated['student_ids'])
            ->update(['course_id' => $validated['course_id'] ?? null]);

        return response()->json([
            'message' => 'Students updated successfully.',
            'updated' => $updated,
        ]);
    }

    public function autoAssignCourses(Request $request)
    {
        $courses = Course::query()
            ->orderBy('id')
            ->get(['id', 'course_name', 'course_code', 'department']);

        if ($courses->isEmpty()) {
            return response()->json([
                'message' => 'No courses available for assignment.',
            ], 422);
        }

        $normalize = static function (?string $value): string {
            $text = strtolower(trim((string) $value));
            return preg_replace('/\s+/', ' ', $text);
        };

        $courseIds = $courses->pluck('id')->map(fn ($id) => (int) $id)->all();
        $courseByDepartment = [];
        foreach ($courses as $course) {
            $deptKey = $normalize($course->department);
            if ($deptKey === '') {
                continue;
            }
            $courseByDepartment[$deptKey] = $courseByDepartment[$deptKey] ?? [];
            $courseByDepartment[$deptKey][] = (int) $course->id;
        }

        $counts = Student::query()
            ->whereNotNull('course_id')
            ->selectRaw('course_id, COUNT(*) as total')
            ->groupBy('course_id')
            ->pluck('total', 'course_id')
            ->map(fn ($n) => (int) $n)
            ->all();

        foreach ($courseIds as $cid) {
            if (! array_key_exists($cid, $counts)) {
                $counts[$cid] = 0;
            }
        }

        $pickLeastLoaded = static function (array $candidateIds, array $loadMap): int {
            usort($candidateIds, static function ($a, $b) use ($loadMap) {
                $la = $loadMap[$a] ?? 0;
                $lb = $loadMap[$b] ?? 0;
                if ($la === $lb) {
                    return $a <=> $b;
                }
                return $la <=> $lb;
            });
            return (int) $candidateIds[0];
        };

        $students = Student::query()
            ->whereNull('course_id')
            ->get(['id', 'department']);

        $assigned = 0;
        $matchedByDepartment = 0;
        $assignedByFallback = 0;

        DB::transaction(function () use (
            $students,
            $normalize,
            $courseByDepartment,
            $courseIds,
            &$counts,
            $pickLeastLoaded,
            &$assigned,
            &$matchedByDepartment,
            &$assignedByFallback
        ) {
            foreach ($students as $student) {
                $dept = $normalize($student->department);
                $candidates = [];

                if ($dept !== '' && isset($courseByDepartment[$dept])) {
                    $candidates = $courseByDepartment[$dept];
                }

                if (empty($candidates) && $dept !== '') {
                    foreach ($courseByDepartment as $knownDept => $ids) {
                        if (str_contains($knownDept, $dept) || str_contains($dept, $knownDept)) {
                            $candidates = array_merge($candidates, $ids);
                        }
                    }
                }

                if (empty($candidates)) {
                    $candidates = $courseIds;
                }

                $candidates = array_values(array_unique(array_map('intval', $candidates)));
                if (empty($candidates)) {
                    continue;
                }

                $chosenCourseId = $pickLeastLoaded($candidates, $counts);

                Student::query()
                    ->where('id', $student->id)
                    ->whereNull('course_id')
                    ->update(['course_id' => $chosenCourseId]);

                $counts[$chosenCourseId] = ($counts[$chosenCourseId] ?? 0) + 1;
                $assigned++;
                if ($dept !== '' && ! empty($courseByDepartment[$dept])) {
                    $matchedByDepartment++;
                } else {
                    $assignedByFallback++;
                }
            }
        });

        return response()->json([
            'message' => 'Auto-assignment complete.',
            'assigned' => $assigned,
            'matched_by_department' => $matchedByDepartment,
            'assigned_by_fallback' => $assignedByFallback,
        ]);
    }

    public function enrollmentTrends()
    {
        $driver = DB::connection()->getDriverName();
        $monthExpr = match ($driver) {
            'pgsql' => "TO_CHAR(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $trends = Student::query()
            ->selectRaw("$monthExpr as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json(['data' => $trends]);
    }
}
