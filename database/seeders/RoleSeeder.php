<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    
    public function run(): void
    {
        Role::firstorCreate(['name' => 'super-admin']);
        Role::firstorCreate(['name' => 'moderator']);
        Role::firstorCreate(['name' => 'student']);

    }
}
