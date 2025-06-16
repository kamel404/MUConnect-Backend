<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //todo put the seeder of faculty and courses so it would run when the applciation runs
        // User::factory(10)->create();

        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AssignPermissionsSeeder::class,
            UserSeeder::class,
            CourseSeeder::class,
            // FacultySeeder::class,
            // MajorSeeder::class,
            VotingStatusSeeder::class,
        ]);
    }
}
