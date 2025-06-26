<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    
    public function run(): void
    {
        // Clear model_has_roles first due to foreign key constraint, then roles
        DB::table('model_has_roles')->delete();
        DB::table('roles')->delete();
        $roles = ['admin', 'moderator', 'student'];

        foreach ($roles as $role) {
            // Create role for default "web" guard
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            // And for API (sanctum) guard
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'sanctum']);
        }
    }
}
