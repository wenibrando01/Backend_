<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SchoolDayResource;
use App\Models\SchoolDay;
use Illuminate\Http\Request;

class SchoolDayController extends Controller
{
    public function index(Request $request)
    {
        $query = SchoolDay::query()->orderBy('date');

        $search = $request->string('search')->trim();
        if ($search->isNotEmpty()) {
            $term = (string) $search;
            $lower = mb_strtolower($term);
            $digits = preg_replace('/\D+/', '', $term) ?? '';

            $query->where(function ($q) use ($term, $lower, $digits) {
                $q->where('description', 'like', '%' . $term . '%')
                    ->orWhere('date', 'like', '%' . $term . '%')
                    ->orWhere('start_time', 'like', '%' . $term . '%')
                    ->orWhere('end_time', 'like', '%' . $term . '%')
                    ->orWhereRaw('CAST(attendance_rate AS CHAR) like ?', ['%' . $term . '%'])
                    ->orWhereRaw("DATE_FORMAT(`date`, '%Y-%m-%d') like ?", ['%' . $term . '%'])
                    ->orWhereRaw("DATE_FORMAT(`date`, '%m/%d/%Y') like ?", ['%' . $term . '%'])
                    ->orWhereRaw("DATE_FORMAT(`date`, '%M') like ?", ['%' . $term . '%'])
                    ->orWhereRaw("DATE_FORMAT(`date`, '%b') like ?", ['%' . $term . '%'])
                    ->orWhereRaw('DAYNAME(`date`) like ?', ['%' . $term . '%']);

                if ($digits !== '') {
                    $q->orWhereRaw("DATE_FORMAT(`date`, '%m%d') like ?", ['%' . $digits . '%'])
                        ->orWhereRaw("DATE_FORMAT(`date`, '%m%d%Y') like ?", ['%' . $digits . '%'])
                        ->orWhereRaw("REPLACE(DATE_FORMAT(`date`, '%m/%d/%Y'), '/', '') like ?", ['%' . $digits . '%']);
                }

                $holidayKeywords = ['holiday', 'walang pasok', 'no class', 'no classes', 'rest day', 'day off'];
                $regularKeywords = ['regular', 'class day', 'may pasok', 'school day'];

                if (collect($holidayKeywords)->contains(fn ($k) => str_contains($lower, $k))) {
                    $q->orWhere('is_holiday', true);
                }

                if (collect($regularKeywords)->contains(fn ($k) => str_contains($lower, $k))) {
                    $q->orWhere('is_holiday', false);
                }
            });
        }

        $perPage = $request->get('per_page');
        $days = $perPage ? $query->paginate(min(max((int) $perPage, 1), 100)) : $query->get();

        return $perPage ? SchoolDayResource::collection($days) : response()->json(['data' => SchoolDayResource::collection($days)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'unique:school_days,date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after_or_equal:start_time'],
            'description' => ['nullable', 'string', 'max:500'],
            'attendance_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_holiday' => ['nullable', 'boolean'],
        ]);

        $validated['attendance_rate'] = $validated['attendance_rate'] ?? 0;
        $validated['is_holiday'] = (bool) ($validated['is_holiday'] ?? false);

        $day = SchoolDay::query()->create($validated);

        return response()->json(new SchoolDayResource($day), 201);
    }

    public function show(string $id)
    {
        $day = SchoolDay::query()->findOrFail($id);
        return response()->json(new SchoolDayResource($day));
    }

    public function update(Request $request, string $id)
    {
        $day = SchoolDay::query()->findOrFail($id);

        $validated = $request->validate([
            'date' => ['sometimes', 'date', 'unique:school_days,date,' . $day->id],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'description' => ['nullable', 'string', 'max:500'],
            'attendance_rate' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_holiday' => ['nullable', 'boolean'],
        ]);

        $day->update($validated);

        return response()->json(new SchoolDayResource($day));
    }

    public function destroy(string $id)
    {
        $day = SchoolDay::query()->findOrFail($id);
        $day->delete();
        return response()->json(['message' => 'School day deleted'], 200);
    }

    public function attendance()
    {
        $attendance = SchoolDay::query()
            ->select(['date', 'attendance_rate', 'is_holiday'])
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $attendance]);
    }
}
