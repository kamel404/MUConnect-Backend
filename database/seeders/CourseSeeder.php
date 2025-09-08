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

        try {
            // Get the JSON file
            $json = File::get($filePath);
            $courses = json_decode($json, true);

            if (!$courses || !is_array($courses)) {
                $this->command->error("Invalid JSON in courses.json or empty file");
                return;
            }

            $this->command->info("Processing " . count($courses) . " courses...");

            // Use database transactions for better performance
            DB::transaction(function () use ($courses) {
                foreach ($courses as $index => $item) {
                    // Add progress indicator for large datasets
                    if ($index % 100 === 0) {
                        $this->command->info("Processed {$index} courses...");
                    }

                    // Validate required fields
                    if (!isset($item['Course']) || !isset($item['Faculty'])) {
                        $this->command->warning("Skipping invalid course data at index {$index}");
                        continue;
                    }

                    $courseName = $item['Course'];

                    // Extract course code and title
                    if (preg_match('/^([A-Z]+\d+)\s*-\s*(.+)$/', $courseName, $matches)) {
                        $code = $matches[1];
                        $name = trim($matches[2]);
                    } else {
                        // fallback if format is unexpected
                        $code = 'GEN' . str_pad($index, 3, '0', STR_PAD_LEFT);
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
                                    'credits' => $item['credits'] ?? 3, // Default to 3 credits
                                    'year' => $item['year'] ?? null,
                                    'semester' => $item['semester'] ?? null,
                                    'code' => $code,
                                    'faculty_id' => $faculty->id,
                                    'major_id' => $major->id,
                                ]);
                            } else {
                                $this->command->warning("Faculty '{$faculty->name}' has no majors, skipping course: {$code}");
                            }
                        }
                    } else {
                        $this->command->warning("Faculty not found: " . $item['Faculty']);
                    }
                }
            });
            
            $this->command->info('Courses seeded successfully!');

        } catch (\Exception $e) {
            $this->command->error('Error seeding courses: ' . $e->getMessage());
            throw $e; // Re-throw to stop the seeding process
        }
    }
}
