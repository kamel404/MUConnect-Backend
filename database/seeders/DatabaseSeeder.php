<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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

        User::factory()->create([
            "first_name" => "ali",
            "last_name" => "testing",
            "username" => "alitesting",
            "email" => " test101@mu.edu.lb",
            "password" => "password123",
            "password_confirmation" => "password123"
        ]);
        //a$this->call(CourseAndFacultySeeder::class);

    }

}
