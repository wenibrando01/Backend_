<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolDay;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentPortalController extends Controller
{
    private const MINOR_UNITS = 3;
    private const MAJOR_UNITS = 4;
    private const MAX_ENROLL_UNITS = 28;

    private const MINOR_SUBJECT_TITLES = [
        'business communication',
        'business law',
        'economics',
        'entrepreneurship',
        'professional ethics',
        'psychological ethics',
        'workplace ethics',
        'research methods',
        'research methods in it',
        'research methods in psychology',
        'research methods in criminology',
        'research in education',
        'research in mathematics education',
        'research in physical education',
        'engineering research',
        'statistics for psychology',
        'business statistics',
        'probability and statistics',
        'values education',
        'general psychology',
    ];

    private function isMinorSubject(string $subjectName): bool
    {
        $name = $this->normalizeSubjectName($subjectName, true);
        return in_array($name, self::MINOR_SUBJECT_TITLES, true);
    }

    private function normalizeSubjectName(string $subjectName, bool $toLower = false): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $subjectName));
        return $toLower ? mb_strtolower($normalized) : $normalized;
    }

    private function unitsForSubject(string $subjectName): int
    {
        return $this->isMinorSubject($subjectName)
            ? self::MINOR_UNITS
            : self::MAJOR_UNITS;
    }

    private function getCurrentStudent(Request $request): Student
    {
        $studentId = $request->user()?->student_id;

        abort_if($studentId === null, 404, 'Student profile not linked to this account.');

        return Student::query()->with('course')->findOrFail($studentId);
    }

    public function profile(Request $request)
    {
        return response()->json($this->getCurrentStudent($request));
    }

    public function myCourses(Request $request)
    {
        $student = $this->getCurrentStudent($request);

        $primaryCourseId = $student->course_id;

        if ($primaryCourseId === null) {
            $primaryCourseId = Enrollment::query()
                ->where('student_id', $student->id)
                ->latest('id')
                ->value('course_id');
        }

        $courses = Course::query()
            ->when($primaryCourseId, fn ($q) => $q->where('id', $primaryCourseId))
            ->orderBy('course_name')
            ->get();

        return response()->json(['data' => $courses]);
    }

    public function enrollments(Request $request)
    {
        $student = $this->getCurrentStudent($request);

        $enrollments = Enrollment::query()
            ->with('course')
            ->where('student_id', $student->id)
            ->whereNotNull('subject_name')
            ->whereRaw("TRIM(subject_name) <> ''")
            ->orderByDesc('enrolled_on')
            ->orderByDesc('id')
            ->get();

        $enrollments = $enrollments
            ->unique(function ($row) {
                $subject = mb_strtolower(trim((string) ($row->subject_name ?? '')));
                return $row->course_id . '::' . $subject;
            })
            ->values();

        return response()->json(['data' => $enrollments]);
    }

    public function enroll(Request $request)
    {
        $student = $this->getCurrentStudent($request);

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'subject_name' => ['required', 'string', 'max:160'],
            'preferred_session' => ['required', 'string', 'in:morning,afternoon'],
            'schedule_label' => ['nullable', 'string', 'max:160'],
            'enrolled_on' => ['nullable', 'date'],
        ]);

        $validated['subject_name'] = $this->normalizeSubjectName($validated['subject_name']);
        $normalizedIncoming = $this->normalizeSubjectName($validated['subject_name'], true);

        $isPrimaryCourse = (int) $validated['course_id'] === (int) $student->course_id;
        if (! $isPrimaryCourse && ! $this->isMinorSubject($validated['subject_name'])) {
            return response()->json([
                'message' => 'Only minor subjects can be enrolled from other courses.',
            ], 422);
        }

        $existingEnrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $validated['course_id'])
            ->get()
            ->first(function ($row) use ($normalizedIncoming) {
                $existing = $this->normalizeSubjectName((string) ($row->subject_name ?? ''), true);
                return $existing !== '' && $existing === $normalizedIncoming;
            });

        if ($existingEnrollment) {
            return response()->json($existingEnrollment->load('course'));
        }

        $currentUnits = Enrollment::query()
            ->where('student_id', $student->id)
            ->get(['subject_name'])
            ->sum(fn ($row) => $this->unitsForSubject((string) ($row->subject_name ?? '')));

        $newUnits = $this->unitsForSubject($validated['subject_name']);
        $projectedUnits = $currentUnits + $newUnits;

        if ($projectedUnits > self::MAX_ENROLL_UNITS) {
            return response()->json([
                'message' => "You can't enroll more units. Current units: {$currentUnits}, subject units: {$newUnits}, max allowed: " . self::MAX_ENROLL_UNITS . '.',
            ], 422);
        }

        $enrolledOn = $validated['enrolled_on'] ?? now()->toDateString();

        $enrollment = Enrollment::query()->firstOrCreate([
            'student_id' => $student->id,
            'course_id' => $validated['course_id'],
            'subject_name' => $validated['subject_name'],
        ], [
            'preferred_session' => $validated['preferred_session'],
            'schedule_label' => $validated['schedule_label'] ?? null,
            'enrolled_on' => $enrolledOn,
        ]);

        return response()->json($enrollment->load('course'), 201);
    }

    public function grades(Request $request)
    {
        $student = $this->getCurrentStudent($request);

        $grades = Grade::query()
            ->with('course')
            ->where('student_id', $student->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        $enrollments = Enrollment::query()
            ->with('course')
            ->where('student_id', $student->id)
            ->orderByDesc('enrolled_on')
            ->orderByDesc('id')
            ->get();

        $gradeByCourse = $grades
            ->groupBy('course_id')
            ->map(fn ($items) => $items->first());

        $rows = $enrollments->map(function ($enrollment) use ($gradeByCourse) {
            $matched = $gradeByCourse->get($enrollment->course_id);
            $isPublished = $matched && ! empty($matched->published_at) && $matched->grade !== null;

            return [
                'id' => 'enrollment-' . $enrollment->id,
                'subject_name' => $enrollment->subject_name,
                'course' => $enrollment->course,
                'semester' => $matched?->semester,
                'school_year' => $matched?->school_year,
                'grade' => $isPublished ? $matched->grade : null,
                'remarks' => $isPublished ? $matched->remarks : null,
                'published_at' => $isPublished ? $matched->published_at : null,
                'status' => $isPublished ? 'Published' : 'Pending',
                'enrolled_on' => $enrollment->enrolled_on,
            ];
        });

        $enrolledCourseIds = $enrollments->pluck('course_id')->unique()->values()->all();

        $gradeOnlyRows = $grades
            ->filter(fn ($grade) => ! in_array($grade->course_id, $enrolledCourseIds, true))
            ->map(function ($grade) {
                $isPublished = ! empty($grade->published_at) && $grade->grade !== null;
                return [
                    'id' => 'grade-' . $grade->id,
                    'subject_name' => null,
                    'course' => $grade->course,
                    'semester' => $grade->semester,
                    'school_year' => $grade->school_year,
                    'grade' => $isPublished ? $grade->grade : null,
                    'remarks' => $isPublished ? $grade->remarks : null,
                    'published_at' => $isPublished ? $grade->published_at : null,
                    'status' => $isPublished ? 'Published' : 'Pending',
                    'enrolled_on' => null,
                ];
            });

        $data = $rows
            ->concat($gradeOnlyRows)
            ->values();

        return response()->json(['data' => $data]);
    }

    public function schedule(Request $request)
    {
        $days = SchoolDay::query()
            ->whereNotNull('start_time')
            ->orderBy('date')
            ->get(['id', 'date', 'start_time', 'end_time', 'description', 'is_holiday']);

        return response()->json(['data' => $days]);
    }

    public function events(Request $request)
    {
        $events = SchoolDay::query()
            ->where(function ($q) {
                $q->whereNotNull('description')
                    ->orWhere('is_holiday', true);
            })
            ->orderBy('date')
            ->get(['id', 'date', 'description', 'is_holiday', 'start_time', 'end_time']);

        return response()->json(['data' => $events]);
    }
}
