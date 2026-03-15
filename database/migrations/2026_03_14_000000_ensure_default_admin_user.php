<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('users')->where('email', 'admin@gmail.com')->exists();
        if ($exists) {
            return;
        }

        $now = now();
        $name = 'System Admin';
        $firstName = 'System';
        $lastName = 'Admin';
        $email = 'admin@gmail.com';
        $password = Hash::make('admin123');
        $role = 'admin';

        $insert = [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'first_name')) {
            $insert['first_name'] = $firstName;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'last_name')) {
            $insert['last_name'] = $lastName;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'username')) {
            $insert['username'] = 'admin';
        }

        DB::table('users')->insert($insert);
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'admin@gmail.com')->delete();
    }
};
