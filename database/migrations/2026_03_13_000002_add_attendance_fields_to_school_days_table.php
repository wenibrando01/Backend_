<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_days')) {
            return;
        }

        Schema::table('school_days', function (Blueprint $table) {
            if (! Schema::hasColumn('school_days', 'attendance_rate')) {
                $table->unsignedTinyInteger('attendance_rate')->default(0)->after('date');
            }
            if (! Schema::hasColumn('school_days', 'is_holiday')) {
                $table->boolean('is_holiday')->default(false)->after('attendance_rate');
            }
        });

        // Backfill from legacy columns if they exist
        if (Schema::hasColumn('school_days', 'is_school_day')) {
            DB::table('school_days')
                ->whereNull('is_holiday')
                ->update(['is_holiday' => DB::raw('NOT is_school_day')]);
        }

        if (Schema::hasColumn('school_days', 'type')) {
            DB::table('school_days')
                ->where('type', 'holiday')
                ->update(['is_holiday' => 1, 'attendance_rate' => 0]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('school_days')) {
            return;
        }

        Schema::table('school_days', function (Blueprint $table) {
            if (Schema::hasColumn('school_days', 'attendance_rate')) {
                $table->dropColumn('attendance_rate');
            }
            if (Schema::hasColumn('school_days', 'is_holiday')) {
                $table->dropColumn('is_holiday');
            }
        });
    }
};

