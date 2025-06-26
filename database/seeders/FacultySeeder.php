<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Faculty;
use Illuminate\Support\Facades\DB;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    //todo make seeding database be automated on production so it will not be needed to be run manually
    public function run(): void
    {
        DB::table('courses')->delete();
        DB::table('faculties')->delete();
        DB::statement('ALTER TABLE faculties AUTO_INCREMENT = 1;');
        $faculties = [
            [
                'name' => 'Faculty of Sciences',
                'abbreviation' => 'FoS'
            ],
            [
                'name' => 'Faculty of Business Administration',
                'abbreviation' => 'FBA'
            ],
            [
                'name' => 'Faculty of Engineering',
                'abbreviation' => 'FoE'
            ],
            [
                'name' => 'Faculty of Mass Communication and Fine Arts',
                'abbreviation' => 'MCFA'
            ],
            [
                'name' => 'Faculty of Health Sciences',
                'abbreviation' => 'FHS'
            ],
            [
                'name' => 'Faculty of Education',
                'abbreviation' => 'FED'
            ],
            [
                'name' => 'Faculty of Religions & Human Sciences',
                'abbreviation' => 'FRH'

            ],
            [ //here we have a problem with the abbreviation
                'name' => 'Department of Translation and Languages Department',
                'abbreviation' => 'TL'
            ],
        ];

        foreach ($faculties as $faculty) {
            Faculty::create($faculty);
        }
    }

}
