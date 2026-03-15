<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    private function isAllowedGrade(float $value): bool
    {
        $allowed = [1.0, 2.0, 2.25, 2.5, 2.75, 3.0, 3.25, 3.5, 3.75, 4.0, 7.1, 7.2];
        foreach ($allowed as $a) {
            if (abs($value - $a) < 0.001) {
                return true;
            }
        }
        return false;
    }

    private function deriveRemarks(?float $value): ?string
    {
        if ($value === null) return null;
        if (abs($value - 1.0) < 0.001) return 'FAILED';
        if ($value >= 2.0 && $value <= 4.0) return 'PASSED';
        if (abs($value - 7.1) < 0.001 || abs($value - 7.2) < 0.001) return 'INC';
        return null;
    }

    public function index(Request $request)
    {
        $query = Grade::query()->with(['student', 'course'])->orderByDesc('id');

        $search = $request->string('search')->trim();
        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search) {
                $q->where('semester', 'like', '%' . $search . '%')
                    ->orWhere('school_year', 'like', '%' . $search . '%')
                    ->orWhere('remarks', 'like', '%' . $search . '%');
            });
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);
        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'semester' => ['required', 'string', 'max:32'],
            'school_year' => ['required', 'string', 'max:32'],
            'grade' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) {
                    $v = (float) $value;
                    if (! $this->isAllowedGrade($v)) {
                        $fail('Grade must be one of: 1.0, 2.0-4.0 (including 0.25 steps), 7.1, or 7.2.');
                    }
                },
            ],
            'remarks' => ['nullable', 'string', 'max:64'],
            'published_at' => ['nullable', 'date'],
        ]);

        $gradeValue = array_key_exists('grade', $validated) && $validated['grade'] !== null
            ? (float) $validated['grade']
            : null;
        $remarks = $this->deriveRemarks($gradeValue) ?? ($validated['remarks'] ?? null);

        $grade = Grade::query()->updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'course_id' => $validated['course_id'],
                'semester' => $validated['semester'],
                'school_year' => $validated['school_year'],
            ],
            [
                'grade' => $gradeValue,
                'remarks' => $remarks,
                'published_at' => $validated['published_at'] ?? now(),
            ]
        );

        return response()->json($grade->load(['student', 'course']), 201);
    }

    public function update(Request $request, string $id)
    {
        $grade = Grade::query()->findOrFail($id);

        $validated = $request->validate([
            'semester' => ['sometimes', 'string', 'max:32'],
            'school_year' => ['sometimes', 'string', 'max:32'],
            'grade' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) {
                    $v = (float) $value;
                    if (! $this->isAllowedGrade($v)) {
                        $fail('Grade must be one of: 1.0, 2.0-4.0 (including 0.25 steps), 7.1, or 7.2.');
                    }
                },
            ],
            'remarks' => ['nullable', 'string', 'max:64'],
            'published_at' => ['nullable', 'date'],
        ]);

        if (array_key_exists('grade', $validated)) {
            $gradeValue = $validated['grade'] !== null ? (float) $validated['grade'] : null;
            $validated['grade'] = $gradeValue;
            $validated['remarks'] = $this->deriveRemarks($gradeValue);
        }

        $grade->update($validated);

        return response()->json($grade->load(['student', 'course']));
    }

    public function destroy(string $id)
    {
        $grade = Grade::query()->findOrFail($id);
        $grade->delete();

        return response()->json(['message' => 'Grade deleted']);
    }
}
