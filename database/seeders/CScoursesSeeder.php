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
        DB::table('courses')->delete();
        
        // Use database/seeders/data path instead
        $filePath = database_path('seeders/data/CScourses.json');
        
        if (!File::exists($filePath)) {
            $this->command->error("File not found: {$filePath}");
            return;
        }
        
        $json = File::get($filePath);
        $courses = json_decode($json, true);
        
        foreach ($courses as $course) {
            Course::create([
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