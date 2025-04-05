<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignPermissionsSeeder extends Seeder
{
    public function run(): void
    {

        // Fetch roles
        $superAdmin = Role::where('name', 'super-admin')->first();
        $moderator = Role::where('name', 'moderator')->first();
        $student = Role::where('name', 'student')->first();

        // Fetch permissions
        $allPermissions = Permission::all(); // All permissions

        // Assign all permissions to Super Admin
        if ($superAdmin) {
            $superAdmin->syncPermissions($allPermissions);
        }

        // Assign specific permissions to Moderator
        if ($moderator) {
            $moderator->syncPermissions([
                'get faculties',
                'show faculty',
                'create faculty',
                'update faculty',
                'delete faculty',
                'get majors',
                'create major',
                'update major',
                'view major',
                'delete major',
                'get users',
                'view user',
                'update user',
                'delete user',
                'view user roles',
                'update user roles',
            ]);
        }

        // Assign limited permissions to Student
        if ($student) {
            $student->syncPermissions([
                'register',
                'login',
                'logout',
                'view faculty majors',
                'view major students',
                'search faculty',
                'search major',
            ]);
        }

        $this->command->info('Permissions assigned to roles successfully.');
    }
}
