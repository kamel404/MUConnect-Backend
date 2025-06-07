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
        $admin = Role::where('name', 'admin')->first();
        $moderator = Role::where('name', 'moderator')->first();
        $student = Role::where('name', 'student')->first();

        $allPermissions = Permission::all();

        // Admin gets all permissions
        if ($admin) {
            $admin->syncPermissions($allPermissions);
        }

        // Moderator gets all permissions (or subset if needed)
        if ($moderator) {
            $moderator->syncPermissions($allPermissions);
        }

        // Student gets limited permissions
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
