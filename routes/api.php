<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SchoolDayController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\StudentPortalController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\PrivateMessageController;

// Public: Student registration (role is always student, enforced in backend)
Route::post('/register', [AuthController::class, 'register']);

// Public: Role-specific login
Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::post('/student/login', [AuthController::class, 'studentLogin']);

// Shared auth (both admin and student can logout / get user)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::patch('/user', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
});

// Student-only routes (auth + role = student)
Route::middleware(['auth:sanctum', 'student'])->group(function () {
    Route::get('/students/enrollment-trends', [StudentController::class, 'enrollmentTrends']);
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/distribution', [CourseController::class, 'distribution']);
    Route::get('/school-days', [SchoolDayController::class, 'index']);
    Route::get('/attendance', [SchoolDayController::class, 'attendance']);
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    Route::get('/student/profile', [StudentPortalController::class, 'profile']);
    Route::get('/student/my-courses', [StudentPortalController::class, 'myCourses']);
    Route::get('/student/enrollments', [StudentPortalController::class, 'enrollments']);
    Route::post('/student/enrollments', [StudentPortalController::class, 'enroll']);
    Route::get('/student/grades', [StudentPortalController::class, 'grades']);
    Route::get('/student/schedule', [StudentPortalController::class, 'schedule']);
    Route::get('/student/events', [StudentPortalController::class, 'events']);
    Route::get('/student/private-messages', [PrivateMessageController::class, 'studentInbox']);
    Route::post('/student/private-messages/mark-read', [PrivateMessageController::class, 'markStudentRead']);
});

// Admin-only routes (auth + role = admin)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
    Route::get('/dashboard/recent-activity', [AdminDashboardController::class, 'recentActivity']);

    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::post('/students/bulk-course', [StudentController::class, 'bulkAssignCourse']);
    Route::post('/students/auto-assign-courses', [StudentController::class, 'autoAssignCourses']);
    Route::get('/enrolled-subjects', [StudentController::class, 'enrolledSubjects']);
    Route::patch('/enrolled-subjects/{id}', [StudentController::class, 'updateEnrolledSubject']);
    Route::delete('/enrolled-subjects/{id}', [StudentController::class, 'destroyEnrolledSubject']);
    Route::get('/students/{id}/enrolled-courses', [StudentController::class, 'enrolledCourses']);
    Route::get('/students/{id}', [StudentController::class, 'show']);
    Route::put('/students/{id}', [StudentController::class, 'update']);
    Route::patch('/students/{id}', [StudentController::class, 'update']);
    Route::delete('/students/{id}', [StudentController::class, 'destroy']);

    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/distribution', [CourseController::class, 'distribution']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::get('/courses/{id}', [CourseController::class, 'show']);
    Route::put('/courses/{id}', [CourseController::class, 'update']);
    Route::patch('/courses/{id}', [CourseController::class, 'update']);
    Route::delete('/courses/{id}', [CourseController::class, 'destroy']);

    Route::get('/school-days', [SchoolDayController::class, 'index']);
    Route::post('/school-days', [SchoolDayController::class, 'store']);
    Route::get('/school-days/{id}', [SchoolDayController::class, 'show']);
    Route::put('/school-days/{id}', [SchoolDayController::class, 'update']);
    Route::patch('/school-days/{id}', [SchoolDayController::class, 'update']);
    Route::delete('/school-days/{id}', [SchoolDayController::class, 'destroy']);

    Route::get('/announcements', [AnnouncementController::class, 'adminIndex']);
    Route::post('/announcements', [AnnouncementController::class, 'store']);
    Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);
    Route::put('/announcements/{id}', [AnnouncementController::class, 'update']);
    Route::patch('/announcements/{id}', [AnnouncementController::class, 'update']);
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);

    Route::get('/grades', [GradeController::class, 'index']);
    Route::post('/grades', [GradeController::class, 'store']);
    Route::patch('/grades/{id}', [GradeController::class, 'update']);
    Route::delete('/grades/{id}', [GradeController::class, 'destroy']);

    Route::get('/private-messages', [PrivateMessageController::class, 'adminIndex']);
    Route::post('/private-messages', [PrivateMessageController::class, 'store']);
});
