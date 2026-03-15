<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('enrollments', 'subject_name')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->string('subject_name')->nullable()->after('course_id');
            });
        }

        if (!Schema::hasColumn('enrollments', 'preferred_session')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->string('preferred_session', 16)->nullable()->after('subject_name');
            });
        }

        if (!Schema::hasColumn('enrollments', 'schedule_label')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->string('schedule_label', 160)->nullable()->after('preferred_session');
            });
        }

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index('student_id', 'enrollments_student_id_idx');
            });
        } catch (\Throwable $e) {
            // Index already exists or cannot be created in this state.
        }

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index('course_id', 'enrollments_course_id_idx');
            });
        } catch (\Throwable $e) {
            // Index already exists or cannot be created in this state.
        }

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropUnique('enrollments_student_id_course_id_enrolled_on_unique');
            });
        } catch (\Throwable $e) {
            // Old unique may already be removed.
        }

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->unique(['student_id', 'course_id', 'subject_name'], 'enrollments_student_course_subject_unique');
            });
        } catch (\Throwable $e) {
            // New unique may already exist.
        }
    }

    public function down(): void
    {
        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropUnique('enrollments_student_course_subject_unique');
            });
        } catch (\Throwable $e) {
            // Unique may not exist.
        }

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->unique(['student_id', 'course_id', 'enrolled_on'], 'enrollments_student_id_course_id_enrolled_on_unique');
            });
        } catch (\Throwable $e) {
            // Old unique already exists.
        }

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropIndex('enrollments_student_id_idx');
            });
        } catch (\Throwable $e) {
            // Index may not exist.
        }

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropIndex('enrollments_course_id_idx');
            });
        } catch (\Throwable $e) {
            // Index may not exist.
        }

        $dropColumns = [];
        if (Schema::hasColumn('enrollments', 'subject_name')) $dropColumns[] = 'subject_name';
        if (Schema::hasColumn('enrollments', 'preferred_session')) $dropColumns[] = 'preferred_session';
        if (Schema::hasColumn('enrollments', 'schedule_label')) $dropColumns[] = 'schedule_label';

        if (!empty($dropColumns)) {
            Schema::table('enrollments', function (Blueprint $table) use ($dropColumns) {
                $table->dropColumn($dropColumns);
            });
        }
    }
};
