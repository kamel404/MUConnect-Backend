<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Course;

class CScoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Clear existing courses
        DB::table('courses')->truncate();
        
        // Get the JSON file path
        $json = File::get(base_path('storage/app/data/CScourses.json'));
        
        // Convert JSON to array
        $courses = json_decode($json, true);
        
        // Insert each course into the database
        foreach ($courses as $course) {
            Course::updateOrCreate([
                'code' => $course['code'],
                'title' => $course['title'],
                'credits' => $course['credits'],
                'year' => $course['year'],
                'semester' => $course['semester'],
                'faculty_id' => $course['faculty_id'],
                'major_id' => $course['major_id'],
            ]);
        }
        
        $this->command->info('Computer Science Courses table seeded successfully!');
    }
}