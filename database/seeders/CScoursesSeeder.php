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
        
        // Get the Computer Science major ID
        $csMajor = DB::table('majors')->where('name', 'Computer Science')->first();
        $facultyOfSciences = DB::table('faculties')->where('name', 'Faculty of Sciences')->first();
        
        if (!$csMajor || !$facultyOfSciences) {
            $this->command->error('Computer Science major or Faculty of Sciences not found!');
            return;
        }
        
        foreach ($courses as $course) {
            Course::create([
                'code' => $course['code'],
                'title' => $course['title'],
                'credits' => $course['credits'],
                'year' => $course['year'],
                'semester' => $course['semester'],
                'faculty_id' => $facultyOfSciences->id, // Use actual faculty ID
                'major_id' => $csMajor->id, // Use actual major ID
            ]);
        }
        
        $this->command->info('Computer Science Courses table seeded successfully!');
    }
}