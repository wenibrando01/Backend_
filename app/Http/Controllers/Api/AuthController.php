<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function ensureStudentProfile(User $user): void
    {
        if (($user->student_id ?? null) && Student::query()->whereKey($user->student_id)->exists()) {
            return;
        }

        $first = trim((string) ($user->first_name ?? ''));
        $last = trim((string) ($user->last_name ?? ''));
        $name = trim(($first . ' ' . $last)) ?: ($user->name ?: 'Student');

        $courseId = Course::query()->value('id');
        if ($courseId === null) {
            $courseId = Course::query()->create([
                'course_name' => 'General',
                'department' => 'General',
            ])->id;
        }

        $student = Student::query()->firstOrCreate(
            ['email' => $user->email],
            [
                'name' => $name,
                'first_name' => $first !== '' ? $first : 'Student',
                'last_name' => $last,
                'age' => 18,
                'gender' => 'other',
                'course_id' => $courseId,
                'date_of_birth' => now()->subYears(18)->format('Y-m-d'),
                'department' => 'General',
                'year_level' => 1,
                'status' => 'active',
            ]
        );

        $user->forceFill(['student_id' => $student->id])->save();
    }

    /**
     * Student-only registration. Role is always 'student'; any role from frontend is ignored.
     * Creates a Student record so the new account appears in the admin student list immediately.
     */
    public function register(RegisterRequest $request)
    {
        $first = $request->string('first_name')->toString();
        $last = $request->string('last_name')->toString();
        $email = $request->string('email')->toString();
        $name = trim($first . ' ' . $last) ?: 'Student';
        $username = $request->has('username') && trim((string) $request->input('username')) !== ''
            ? $request->string('username')->toString()
            : str_replace(['@', '.'], ['_', '_'], $email);

        if (Student::query()->where('email', $email)->exists() || User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => ['This email is already registered.']]);
        }

        $user = DB::transaction(function () use ($request, $first, $last, $email, $name, $username) {
            $courseId = Course::query()->value('id');
            if ($courseId === null) {
                $course = Course::query()->create([
                    'course_name' => 'General',
                    'department' => 'General',
                ]);
                $courseId = $course->id;
            }

            $student = Student::query()->create([
                'name' => $name,
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'age' => 18,
                'gender' => 'other',
                'course_id' => $courseId,
                'date_of_birth' => now()->subYears(18)->format('Y-m-d'),
                'department' => 'General',
                'year_level' => 1,
                'status' => 'active',
            ]);

            $user = User::query()->create([
                'first_name' => $first,
                'last_name' => $last,
                'username' => $username,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($request->string('password')->toString()),
                'role' => 'student',
                'student_id' => $student->id,
            ]);

            return $user;
        });

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Admin login. Validates email + password and ensures role = admin.
     */
    public function adminLogin(LoginRequest $request)
    {
        $email = $request->string('email')->toString();
        $password = $request->string('password')->toString();

        $user = User::query()->where('email', mb_strtolower($email))->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        if ($user->role !== 'admin') {
            throw ValidationException::withMessages([
                'email' => ['Access denied. This area is for administrators only.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Student login. Validates email + password and ensures role = student.
     */
    public function studentLogin(LoginRequest $request)
    {
        $identifier = $request->string('email')->toString();
        $credentials = ['password' => $request->string('password')->toString()];

        if (str_contains($identifier, '@')) {
            $credentials['email'] = mb_strtolower($identifier);
        } else {
            $credentials['username'] = $identifier;
        }

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->role !== 'student') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Access denied. Please use the admin login.'],
            ]);
        }

        $this->ensureStudentProfile($user);
        $user->refresh();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! Hash::check($request->string('current_password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->password = Hash::make($request->string('password')->toString());
        $user->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function logout()
    {
        $user = request()->user();
        $user?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    public function user()
    {
        return response()->json(new UserResource(request()->user()));
    }

    public function updateProfile(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'first_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'username' => 'sometimes|nullable|string|max:255|unique:users,username,' . $user->id,
            'email' => 'sometimes|nullable|email|unique:users,email,' . $user->id,
        ]);
        if (!empty($validated['first_name'])) {
            $user->first_name = $validated['first_name'];
        }
        if (array_key_exists('last_name', $validated)) {
            $user->last_name = $validated['last_name'];
        }
        if (array_key_exists('username', $validated)) {
            $user->username = $validated['username'];
        }
        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }
        $user->name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $user->save();
        return response()->json(['user' => new UserResource($user), 'message' => 'Profile updated.']);
    }
}