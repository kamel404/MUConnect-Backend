<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Major;

class MajorSeeder extends Seeder
{
    public function run(): void
    {
        // Use database/seeders/data path
        $filePath = database_path('seeders/data/majors.json');
        
        if (!File::exists($filePath)) {
            $this->command->error("majors.json file not found at {$filePath}");
            return;
        }

        $json = File::get($filePath);
        $majors = json_decode($json, true);

        foreach ($majors as $major) {
            Major::create([
                'name' => $major['name'],
                'abbreviation' => $major['abbreviation'],
                'faculty_id' => $major['faculty_id'],
            ]);
        }

        $this->command->info('Majors table seeded successfully!');
    }

    /**
     * Generate abbreviation from major name
     */
    private function generateAbbreviation($name)
    {
        // Split by spaces and take first letter of each word
        $words = explode(' ', $name);
        $abbreviation = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $abbreviation .= strtoupper($word[0]);
            }
        }
        
        // Fallback if abbreviation is too short
        if (strlen($abbreviation) < 2) {
            $abbreviation = strtoupper(substr($name, 0, 3));
        }
        
        return $abbreviation;
    }
}