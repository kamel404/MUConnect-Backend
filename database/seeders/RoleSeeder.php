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
        DB::table('roles')->truncate();
        $roles = ['admin', 'moderator', 'student'];

        foreach ($roles as $role) {
            // Create role for default "web" guard
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            // And for API (sanctum) guard
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'sanctum']);
        }
    }
}
