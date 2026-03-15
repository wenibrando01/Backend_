<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class CourseSeeder extends Seeder
{
    private const SEEDED_COURSE_CODES = [
        'ABEL',
        'BEED',
        'BSEDENG',
        'BSEDMATH',
        'BPE',
        'BSA',
        'BSCpE',
        'BSCS',
        'BSCRIM',
        'BSEE',
        'BSECE',
        'BSFM',
        'BSHM',
        'BSHRM',
        'BSIT',
        'BSIA',
        'BSMA',
        'BSMM',
        'BSP',
        'BSTM',
    ];

    public function run()
    {
        $courses = [
            ['course_name' => 'AB English Language', 'course_code' => 'ABEL', 'department' => 'Arts and Sciences'],
            ['course_name' => 'Bachelor of Elementary Education (BEED)', 'course_code' => 'BEED', 'department' => 'Education'],
            ['course_name' => 'Bachelor of Secondary Education - English', 'course_code' => 'BSEDENG', 'department' => 'Education'],
            ['course_name' => 'Bachelor of Secondary Education - Mathematics', 'course_code' => 'BSEDMATH', 'department' => 'Education'],
            ['course_name' => 'Bachelor of Physical Education', 'course_code' => 'BPE', 'department' => 'Education'],
            ['course_name' => 'BS Accountancy', 'course_code' => 'BSA', 'department' => 'Business and Accountancy'],
            ['course_name' => 'BS Computer Engineering', 'course_code' => 'BSCpE', 'department' => 'Engineering'],
            ['course_name' => 'BS Computer Science', 'course_code' => 'BSCS', 'department' => 'Computing'],
            ['course_name' => 'BS Criminology', 'course_code' => 'BSCRIM', 'department' => 'Criminology'],
            ['course_name' => 'BS Electrical Engineering', 'course_code' => 'BSEE', 'department' => 'Engineering'],
            ['course_name' => 'BS Electronics Engineering', 'course_code' => 'BSECE', 'department' => 'Engineering'],
            ['course_name' => 'BS Financial Management', 'course_code' => 'BSFM', 'department' => 'Business and Accountancy'],
            ['course_name' => 'BS Hospitality Management', 'course_code' => 'BSHM', 'department' => 'Hospitality and Tourism'],
            ['course_name' => 'BS Human Resource Management', 'course_code' => 'BSHRM', 'department' => 'Business and Accountancy'],
            ['course_name' => 'BS Information Technology', 'course_code' => 'BSIT', 'department' => 'Computing'],
            ['course_name' => 'BS Internal Auditing', 'course_code' => 'BSIA', 'department' => 'Business and Accountancy'],
            ['course_name' => 'BS Management Accounting', 'course_code' => 'BSMA', 'department' => 'Business and Accountancy'],
            ['course_name' => 'BS Marketing Management', 'course_code' => 'BSMM', 'department' => 'Business and Accountancy'],
            ['course_name' => 'BS Psychology', 'course_code' => 'BSP', 'department' => 'Arts and Sciences'],
            ['course_name' => 'BS Tourism Management', 'course_code' => 'BSTM', 'department' => 'Hospitality and Tourism'],
        ];

        $keptIds = [];
        foreach ($courses as $course) {
            $saved = Course::query()->updateOrCreate(
                ['course_name' => $course['course_name']],
                [
                    'course_code' => $course['course_code'],
                    'department' => $course['department'],
                    'status' => 'active',
                ]
            );

            $keptIds[] = $saved->id;
        }

        // Re-assign students from legacy combined BSED and removed BSBA before cleanup.
        $bsedEnglishId = Course::query()->where('course_code', 'BSEDENG')->value('id');
        $legacyCombinedBsedId = Course::query()
            ->where('course_name', 'Bachelor of Secondary Education (BSED) - majors in English, Math, Science, Filipino, Social Studies')
            ->value('id');

        if ($legacyCombinedBsedId && $bsedEnglishId) {
            Student::query()->where('course_id', $legacyCombinedBsedId)->update(['course_id' => $bsedEnglishId]);
        }

        $bsfmId = Course::query()->where('course_code', 'BSFM')->value('id');
        $legacyBsbaId = Course::query()->where('course_code', 'BSBA')->value('id');

        if ($legacyBsbaId && $bsfmId) {
            Student::query()->where('course_id', $legacyBsbaId)->update(['course_id' => $bsfmId]);
        }

        // Keep admin-created or future courses; only ensure the seeded 20 are present and active.
        Course::query()
            ->whereIn('course_code', self::SEEDED_COURSE_CODES)
            ->update(['status' => 'active']);
    }
}