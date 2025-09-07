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
                'faculty_id' => $major['faculty_id'],
            ]);
        }

        $this->command->info('Majors table seeded successfully!');
    }
}