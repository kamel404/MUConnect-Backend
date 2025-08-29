<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Create System Admin User 
        $systemAdmin = User::firstOrCreate(
            ['email' => 'system@mu.edu.lb'],
            [
                'username' => 'system',
                'first_name' => 'System',
                'last_name' => 'Admin',
                'password' => Hash::make(env('SYSTEM_ADMIN_DEFAULT_PASSWORD')),
                'is_active' => true,
                'is_verified' => true,
                'faculty_id' => null,
                'major_id' => null,
                'bio' => 'System Administrator',
            ]
        );
        $systemAdmin->assignRole('admin');
        
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@mu.edu.lb'],
            [
                'username' => 'admin',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD')),
                'is_active' => true,
                'is_verified' => true,
                'faculty_id' => 1,
                'major_id' => 1,
                'bio' => 'System administrator',
            ]
        );
        $admin->assignRole('admin');

        // Create moderator user
        $moderator = User::firstOrCreate(
            ['email' => 'moderator@mu.edu.lb'],
            [
                'username' => 'moderator',
                'first_name' => 'Moderator',
                'last_name' => 'User',
                'password' => Hash::make(env('MODERATOR_DEFAULT_PASSWORD')),
                'is_active' => true,
                'is_verified' => true,
                'faculty_id' => 1,
                'major_id' => 1,
                'bio' => 'Content moderator',
            ]
        );
        $moderator->assignRole('moderator');

        // Create student user
        $student = User::firstOrCreate(
            ['email' => 'student@mu.edu.lb'],
            [
                'username' => 'student',
                'first_name' => 'Student',
                'last_name' => 'User',
                'password' => Hash::make('student'),
                'is_active' => true,
                'is_verified' => true,
                'faculty_id' => 1,
                'major_id' => 1,
                'bio' => 'Regular student user',
            ]
        );
        $student->assignRole('student');
    }
}
