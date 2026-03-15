# School Dashboard API (Laravel)

Laravel REST API backend for the School Dashboard project.

## Tech Stack

- Laravel + Sanctum (token-based API auth)
- MySQL/PostgreSQL

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

## Notes

- API unauthenticated responses return JSON `401` for `/api/*` routes.
- CORS is configured through `config/cors.php` and `.env` values.

## End of README
