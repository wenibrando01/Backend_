<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            ['CS101', 'Introduction to Computer Science', 'Computer Science'],
            ['CS102', 'Data Structures', 'Computer Science'],
            ['CS201', 'Algorithms', 'Computer Science'],
            ['IT101', 'Introduction to Information Technology', 'Information Technology'],
            ['IT201', 'Networks and Communications', 'Information Technology'],
            ['BA101', 'Principles of Management', 'Business Administration'],
            ['BA201', 'Marketing Fundamentals', 'Business Administration'],
            ['ENG101', 'Engineering Mechanics', 'Engineering'],
            ['ENG201', 'Thermodynamics', 'Engineering'],
            ['EDU101', 'Foundations of Education', 'Education'],
            ['EDU201', 'Educational Psychology', 'Education'],
            ['CS301', 'Database Systems', 'Computer Science'],
            ['CS302', 'Web Development', 'Computer Science'],
            ['IT301', 'Cloud Computing', 'Information Technology'],
            ['BA301', 'Financial Accounting', 'Business Administration'],
            ['ENG301', 'Control Systems', 'Engineering'],
            ['EDU301', 'Curriculum Development', 'Education'],
            ['CS303', 'Machine Learning', 'Computer Science'],
            ['IT302', 'Cybersecurity Basics', 'Information Technology'],
            ['BA302', 'Business Analytics', 'Business Administration'],
        ];

        foreach ($courses as [$code, $title, $department]) {
            Course::create([
                'code' => $code,
                'title' => $title,
                'department' => $department,
                'credits' => 3,
            ]);
        }
    }
}
