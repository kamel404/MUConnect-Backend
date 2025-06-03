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
        $moderator = Role::where('name', 'moderator')->first();
        $student = Role::where('name', 'student')->first();

        // Fetch permissions
        $allPermissions = Permission::all(); // All permissions

        // Assign all permissions to Moderator
        if ($moderator) {
            $moderator->syncPermissions($allPermissions);
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
