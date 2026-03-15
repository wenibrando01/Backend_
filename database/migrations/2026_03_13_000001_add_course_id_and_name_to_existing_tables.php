<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- courses: ensure course_name exists (backfill from title if present) ---
        if (Schema::hasTable('courses') && ! Schema::hasColumn('courses', 'course_name')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->string('course_name')->nullable()->after('id');
            });

            if (Schema::hasColumn('courses', 'title')) {
                DB::table('courses')
                    ->whereNull('course_name')
                    ->update(['course_name' => DB::raw('title')]);
            }

            DB::table('courses')
                ->whereNull('course_name')
                ->update(['course_name' => 'Unnamed Course']);

            Schema::table('courses', function (Blueprint $table) {
                $table->string('course_name')->nullable(false)->change();
            });
        }

        // --- students: ensure course_id exists and is FK ---
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'course_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('course_id')->nullable()->after('gender');
            });

            // Backfill: prefer latest enrollment course, then fallback to first available course.
            if (Schema::hasTable('courses')) {
                if (Schema::hasTable('enrollments')) {
                    $driver = DB::connection()->getDriverName();

                    if ($driver === 'pgsql') {
                        DB::statement("
                            UPDATE students s
                            SET course_id = e.course_id
                            FROM (
                                SELECT DISTINCT ON (student_id)
                                    student_id,
                                    course_id
                                FROM enrollments
                                ORDER BY student_id, enrolled_on DESC NULLS LAST, id DESC
                            ) e
                            WHERE s.id = e.student_id
                              AND s.course_id IS NULL
                        ");
                    } else {
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
                            ) e ON e.student_id = s.id
                            SET s.course_id = e.course_id
                            WHERE s.course_id IS NULL
                        ");
                    }
                }

                $courseIds = DB::table('courses')->pluck('id')->all();
                if (! empty($courseIds)) {
                    $defaultCourseId = $courseIds[0];
                    DB::table('students')
                        ->whereNull('course_id')
                        ->update(['course_id' => $defaultCourseId]);
                }
            }

            // Add FK constraint if courses table exists
            if (Schema::hasTable('courses')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
                });
            }

            Schema::table('students', function (Blueprint $table) {
                $table->unsignedBigInteger('course_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'course_id')) {
            Schema::table('students', function (Blueprint $table) {
                try {
                    $table->dropForeign(['course_id']);
                } catch (Throwable) {
                    // ignore if FK not present
                }
                $table->dropColumn('course_id');
            });
        }

        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'course_name')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('course_name');
            });
        }
    }
};

