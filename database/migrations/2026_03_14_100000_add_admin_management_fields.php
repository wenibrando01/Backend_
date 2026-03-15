<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('students', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('students', 'year_level')) {
                $table->unsignedTinyInteger('year_level')->default(1)->after('course_id');
            }
            if (! Schema::hasColumn('students', 'status')) {
                $table->string('status', 32)->default('active')->after('year_level');
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'course_code')) {
                $table->string('course_code', 32)->nullable()->after('id');
            }
            if (! Schema::hasColumn('courses', 'description')) {
                $table->text('description')->nullable()->after('department');
            }
            if (! Schema::hasColumn('courses', 'status')) {
                $table->string('status', 32)->default('active')->after('description');
            }
        });

        Schema::table('school_days', function (Blueprint $table) {
            if (! Schema::hasColumn('school_days', 'start_time')) {
                $table->time('start_time')->nullable()->after('date');
            }
            if (! Schema::hasColumn('school_days', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }
            if (! Schema::hasColumn('school_days', 'description')) {
                $table->string('description')->nullable()->after('end_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('students', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (Schema::hasColumn('students', 'year_level')) {
                $table->dropColumn('year_level');
            }
            if (Schema::hasColumn('students', 'status')) {
                $table->dropColumn('status');
            }
        });
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'course_code')) {
                $table->dropColumn('course_code');
            }
            if (Schema::hasColumn('courses', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('courses', 'status')) {
                $table->dropColumn('status');
            }
        });
        Schema::table('school_days', function (Blueprint $table) {
            if (Schema::hasColumn('school_days', 'start_time')) {
                $table->dropColumn('start_time');
            }
            if (Schema::hasColumn('school_days', 'end_time')) {
                $table->dropColumn('end_time');
            }
            if (Schema::hasColumn('school_days', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
