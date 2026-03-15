<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students') || ! Schema::hasTable('enrollments') || ! Schema::hasTable('grades')) {
            return;
        }

        $totalStudents = (int) DB::table('students')->count();
        if ($totalStudents === 0) {
            return;
        }

        $top = DB::table('students')
            ->select('course_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('course_id')
            ->groupBy('course_id')
            ->orderByDesc('total')
            ->first();

        if (! $top || empty($top->course_id)) {
            return;
        }

        $dominantCount = (int) ($top->total ?? 0);
        $dominantRatio = $dominantCount / max(1, $totalStudents);
        $enrollmentRows = (int) DB::table('enrollments')->count();

        // Only run when data is clearly fallback-skewed.
        if ($dominantRatio < 0.90 || $enrollmentRows > (int) floor($totalStudents * 0.20)) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(
                'UPDATE students s
                 SET course_id = NULL
                 WHERE s.course_id = ?
                   AND NOT EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id = s.id)
                   AND NOT EXISTS (SELECT 1 FROM grades g WHERE g.student_id = s.id)',
                [$top->course_id]
            );

            return;
        }

        DB::statement(
            'UPDATE students s
             LEFT JOIN enrollments e ON e.student_id = s.id
             LEFT JOIN grades g ON g.student_id = s.id
             SET s.course_id = NULL
             WHERE s.course_id = ?
               AND e.id IS NULL
               AND g.id IS NULL',
            [$top->course_id]
        );
    }

    public function down(): void
    {
        // One-way data cleanup.
    }
};
