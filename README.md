# School Dashboard API (Laravel)

Laravel REST API backend for the School Dashboard project.


## Tech Stack

### Backend
- **PHP**: ^8.2
- **Laravel Framework**: ^12.0
- **Laravel Sanctum**: ^4.3
- **Laravel Tinker**: ^2.10.1

**Dev dependencies:**
- FakerPHP/Faker: ^1.23
- Laravel Pint: ^1.24
- Laravel Sail: ^1.41
- PHPUnit: ^11.5.3
- Mockery: ^1.6
- Nunomaduro/Collision: ^8.6

### Frontend
- **Vite**: ^7.0.7
- **TailwindCSS**: ^4.0.0
- **@tailwindcss/vite**: ^4.0.0
- **Laravel Vite Plugin**: ^2.0.0
- **Axios**: ^1.11.0
- **Concurrently**: ^9.0.1

---

## Requirements

### Backend
- PHP 8.1 or higher
- Composer
- MySQL or PostgreSQL

### Frontend
- Node.js 18+ and npm

---

## Setup

1. Install dependencies.

```bash
composer install
```

2. Configure environment.

```bash
copy .env.example .env
php artisan key:generate
```

3. Update database and CORS values in `.env`.

- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `CORS_ALLOWED_ORIGINS` (example: `http://localhost:5173,http://127.0.0.1:5173`)

4. Run migrations and seeders.

```bash
php artisan migrate:fresh --seed
```

5. Start backend server.

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

## Seed Data

`php artisan migrate:fresh --seed` runs:

- `CourseSeeder` -> 20+ courses across departments
- `StudentSeeder` -> 500 students with demographic fields
- `SchoolDaySeeder` -> full-year school days with attendance, holiday flags, and event descriptions
- `EnsureAdminSeeder` -> default admin user

## Authentication (Sanctum)

Use `Authorization: Bearer <token>` for protected endpoints.

Public endpoints:

- `POST /api/register` (student registration)
- `POST /api/admin/login`
- `POST /api/student/login`

Authenticated endpoints:

- `POST /api/logout`
- `GET /api/user`
- `PATCH /api/user`
- `POST /api/change-password`

## API Routes Summary

Student routes (`auth:sanctum` + `student` middleware):

- `GET /api/students/enrollment-trends`
- `GET /api/courses`
- `GET /api/courses/distribution`
- `GET /api/school-days`
- `GET /api/attendance`
- `GET /api/dashboard/summary`

Admin routes (`auth:sanctum` + `admin` middleware, `/api/admin/*`):

- `GET /api/admin/dashboard/stats`
- `GET /api/admin/dashboard/recent-activity`
- CRUD for students, courses, and school-days

## API Endpoints

### Public Endpoints
- `POST /api/register` — Student registration
- `POST /api/admin/login` — Admin login
- `POST /api/student/login` — Student login

### Authenticated Endpoints (Requires Bearer Token)
- `POST /api/logout` — Logout
- `GET /api/user` — Get current user info
- `PATCH /api/user` — Update user profile
- `POST /api/change-password` — Change password

### Student Endpoints (Requires student role)
- `GET /api/students/enrollment-trends` — Enrollment trends
- `GET /api/courses` — List courses
- `GET /api/courses/distribution` — Course distribution
- `GET /api/school-days` — List school days
- `GET /api/attendance` — Attendance info
- `GET /api/announcements` — Announcements
- `GET /api/dashboard/summary` — Dashboard summary
- `GET /api/student/profile` — Student profile
- `GET /api/student/my-courses` — My courses
- `GET /api/student/enrollments` — My enrollments
- `POST /api/student/enrollments` — Enroll in course
- `GET /api/student/grades` — My grades
- `GET /api/student/schedule` — My schedule
- `GET /api/student/events` — My events
- `GET /api/student/private-messages` — My private messages
- `POST /api/student/private-messages/mark-read` — Mark messages as read

### Admin Endpoints (Requires admin role, all prefixed with `/api/admin`)
- `GET /api/admin/dashboard/stats` — Dashboard stats
- `GET /api/admin/dashboard/recent-activity` — Recent activity
- `GET /api/admin/students` — List students
- `POST /api/admin/students` — Create student
- `POST /api/admin/students/bulk-course` — Bulk assign course
- `POST /api/admin/students/auto-assign-courses` — Auto-assign courses
- `GET /api/admin/enrolled-subjects` — List enrolled subjects
- `PATCH /api/admin/enrolled-subjects/{id}` — Update enrolled subject
- `DELETE /api/admin/enrolled-subjects/{id}` — Delete enrolled subject
- `GET /api/admin/students/{id}/enrolled-courses` — Student's enrolled courses
- `GET /api/admin/students/{id}` — Show student
- `PUT /api/admin/students/{id}` — Update student
- `PATCH /api/admin/students/{id}` — Update student
- `DELETE /api/admin/students/{id}` — Delete student
- `GET /api/admin/courses` — List courses
- `GET /api/admin/courses/distribution` — Course distribution
- `POST /api/admin/courses` — Create course
- `GET /api/admin/courses/{id}` — Show course
- `PUT /api/admin/courses/{id}` — Update course
- `PATCH /api/admin/courses/{id}` — Update course
- `DELETE /api/admin/courses/{id}` — Delete course
- `GET /api/admin/school-days` — List school days
- `POST /api/admin/school-days` — Create school day
- `GET /api/admin/school-days/{id}` — Show school day
- `PUT /api/admin/school-days/{id}` — Update school day
- `PATCH /api/admin/school-days/{id}` — Update school day
- `DELETE /api/admin/school-days/{id}` — Delete school day
- `GET /api/admin/announcements` — List announcements
- `POST /api/admin/announcements` — Create announcement
- `GET /api/admin/announcements/{id}` — Show announcement
- `PUT /api/admin/announcements/{id}` — Update announcement
- `PATCH /api/admin/announcements/{id}` — Update announcement
- `DELETE /api/admin/announcements/{id}` — Delete announcement
- `GET /api/admin/grades` — List grades
- `POST /api/admin/grades` — Create grade
- `PATCH /api/admin/grades/{id}` — Update grade
- `DELETE /api/admin/grades/{id}` — Delete grade
- `GET /api/admin/private-messages` — List private messages
- `POST /api/admin/private-messages` — Create private message

### Miscellaneous
- `GET /api/token-test` — Test token endpoint (returns a test token)

All endpoints return JSON responses. Authentication uses Laravel Sanctum (token-based). For more details, see the controller methods in the backend code.

---

## Troubleshooting

- **Port conflicts:** If port 8000 (backend) or 5173 (frontend) is in use, change the port in the serve command or Vite config.
- **CORS errors:** Ensure `CORS_ALLOWED_ORIGINS` in backend `.env` matches your frontend URL.
- **Database errors:** Double-check your DB credentials and that the database exists.
- **.env files:** Make sure to copy `.env.example` to `.env` and fill in all required values for both backend and frontend.

---

## Quick Start (from scratch)

1. Clone both repos to your computer.
2. Follow the setup steps in each README.
3. Start backend and frontend in separate terminals.
4. Open http://localhost:5173/ in your browser.

---

## .env.example (Frontend)

```
VITE_API_BASE_URL=http://127.0.0.1:8000/api
VITE_AUTH_LOGIN_PATH=/student/login
VITE_AUTH_REGISTER_PATH=/register
VITE_AUTH_LOGOUT_PATH=/logout
VITE_OPENWEATHER_API_KEY=your_openweather_key_here
```
