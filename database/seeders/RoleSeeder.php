<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    
    public function run(): void
    {
        Role::firstorCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstorCreate(['name' => 'moderator', 'guard_name' => 'web']);
        Role::firstorCreate(['name' => 'student', 'guard_name' => 'web']);

    }
}
