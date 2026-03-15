<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('semester', 32)->default('1st Semester');
            $table->string('school_year', 32)->default('2026-2027');
            $table->decimal('grade', 5, 2)->nullable();
            $table->string('remarks', 64)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'published_at']);
            $table->unique(['student_id', 'course_id', 'semester', 'school_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
