<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students') || ! Schema::hasTable('enrollments') || ! Schema::hasTable('courses')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("
                UPDATE students s
                SET course_id = latest.course_id
                FROM (
                    SELECT DISTINCT ON (e.student_id)
                        e.student_id,
                        e.course_id
                    FROM enrollments e
                    INNER JOIN courses c ON c.id = e.course_id
                    ORDER BY e.student_id, e.enrolled_on DESC NULLS LAST, e.id DESC
                ) AS latest
                WHERE s.id = latest.student_id
                  AND (s.course_id IS NULL OR s.course_id <> latest.course_id)
            ");

            return;
        }

        DB::statement("
            UPDATE students s
            JOIN (
                SELECT e1.student_id, e1.course_id
                FROM enrollments e1
                INNER JOIN (
                    SELECT student_id, MAX(id) AS max_id
                    FROM enrollments
                    GROUP BY student_id
                ) latest ON latest.max_id = e1.id
                INNER JOIN courses c ON c.id = e1.course_id
            ) latest_course ON latest_course.student_id = s.id
            SET s.course_id = latest_course.course_id
            WHERE s.course_id IS NULL OR s.course_id <> latest_course.course_id
        ");
    }

    public function down(): void
    {
        // One-time data repair; no rollback.
    }
};
