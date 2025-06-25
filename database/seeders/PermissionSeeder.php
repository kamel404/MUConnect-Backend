<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permissions')->truncate();

        $permissions = [
            // Faculty 
            'get faculties', // index
            'show faculty', // show
            'create faculty', // store
            'update faculty',
            'delete faculty',
            'view faculty majors', // getFacultyMajors
            'search faculty', // search
            // Major
            'get majors', // index
            'create major', //store
            'update major',
            'view major', //show
            'delete major',
            'view major students', // getMajorStudents
            'search major',
            // User
            'get users', // index
            'view user', // show
            'update user',
            'delete user',
            'view user roles', // getUserRole
            'update user roles', //updateUserRole
            // Auth
            'register',
            'login',
            'logout',
        ];

        $guards = ['web', 'sanctum'];

        foreach ($guards as $guard) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => $guard,
                ]);
            }
        }
    }
}
