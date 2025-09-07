<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Faculty;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check for JSON file in the new location
        $filePath = database_path('seeders/data/courses.json');
        
        if (!File::exists($filePath)) {
            $this->command->info("courses.json not found at {$filePath}, skipping CourseSeeder");
            return;
        }

        // Get the JSON file
        $json = File::get($filePath);
        $courses = json_decode($json, true);

        if (!$courses) {
            $this->command->error("Invalid JSON in courses.json");
            return;
        }

        foreach ($courses as $item) {
            $courseName = $item['Course'];

            // Extract course code and title
            if (preg_match('/^([A-Z]+\d+)\s*-\s*(.+)$/', $courseName, $matches)) {
                $code = $matches[1];
                $name = $matches[2];
            } else {
                // fallback if format is unexpected
                $code = Str::random(6);
                $name = $courseName;
            }
            $faculty = Faculty::where('abbreviation', $item['Faculty'])->first();

            if ($faculty) {
                // Check if course already exists with same code and faculty
                $existing = Course::where('code', $code)
                    ->where('faculty_id', $faculty->id)
                    ->exists();

                if (!$existing) {
                    // Get the first major for this faculty, or skip if none exists
                    $major = $faculty->majors()->first();
                    
                    if ($major) {
                        Course::create([
                            'title' => $name,
                            'credits' => 0,
                            'year' => null,
                            'semester' => null,
                            'code' => $code,
                            'faculty_id' => $faculty->id,
                            'major_id' => $major->id,
                        ]);
                    } else {
                        // Log warning if faculty has no majors
                        logger()->warning("Faculty '{$faculty->name}' has no majors, skipping course: {$code}");
                    }
                }
            } else {
                // optionally log or throw if faculty is missing
                logger()->warning("Faculty not found: " . $item['Faculty']);
            }
        }
        
        $this->command->info('Courses seeded successfully!');
    }
}
