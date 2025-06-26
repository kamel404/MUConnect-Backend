<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MajorSeeder extends Seeder
{
    public function run()
    {
        DB::table('majors')->delete();
        DB::statement('ALTER TABLE majors AUTO_INCREMENT = 1;');
        $jsonPath = base_path('storage/app/data/majors.json');
        if (!File::exists($jsonPath)) {
            $this->command->error("majors.json file not found at $jsonPath");
            return;
        }

        $majors = json_decode(File::get($jsonPath), true);

        if (is_array($majors)) {
            DB::table('majors')->insert($majors);
            $this->command->info('Majors table seeded!');
        } else {
            $this->command->error('Invalid JSON in majors.json');
        }
    }
}