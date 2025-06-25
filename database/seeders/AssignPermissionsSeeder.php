<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignPermissionsSeeder extends Seeder
{
    public function run(): void
    {
                $guards = ['web', 'sanctum'];

        foreach ($guards as $guard) {
            // Fetch the role for the current guard
            $admin     = Role::where('name', 'admin')->where('guard_name', $guard)->first();
            $moderator = Role::where('name', 'moderator')->where('guard_name', $guard)->first();
            $student   = Role::where('name', 'student')->where('guard_name', $guard)->first();

            // Fetch permissions for the same guard
            $permissionsForGuard = Permission::where('guard_name', $guard)->get();

            // Admin gets all permissions of its guard
            if ($admin) {
                $admin->syncPermissions($permissionsForGuard);
            }

            // Moderator gets all permissions of its guard (adjust if subset later)
            if ($moderator) {
                $moderator->syncPermissions($permissionsForGuard);
            }

            // Student gets limited permissions of its guard
            if ($student) {
                $student->syncPermissions($permissionsForGuard->whereIn('name', [
                    'register',
                    'login',
                    'logout',
                    'view faculty majors',
                    'view major students',
                    'search faculty',
                    'search major',
                ]));
            }
        }

        $this->command->info('Permissions assigned to roles successfully.');
    }
}
