<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Faculty;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    //todo make seeding database be automated on production so it will not be needed to be run manually
    public function run(): void
    {
        $faculties = [
            [
                'name' => 'Faculty of Engineering',
                'description' => 'Offers a wide range of engineering majors.',
                'abbreviation' => 'FoE'
            ],
            [
                'name' => 'Faculty of Business Administration',
                'description' => 'Focuses on business, economics, and management programs.',
                'abbreviation' => 'FBA'
            ],
            [
                'name' => 'Faculty of Sciences',
                'description' => 'Covers natural and applied sciences.',
                'abbreviation' => 'FoS'
            ],
            [
                'name' => 'Faculty of Mass Communication and Fine Arts',
                'description' => 'Includes majors in arts, literature, and languages.',
                'abbreviation' => 'MCFA'
            ],
            [
                'name' => 'Faculty of Health Sciences',
                'description' => 'Includes major related to Mass Communiation and Fine Arts.',
                'abbreviation' => 'FHS'
            ],
            [
                'name' => 'Faculty of Education',
                'description' => 'Includes majors related to education.',
                'abbreviation' => 'FED'
            ],
            [
                'name' => 'Faculty of Religions & Human Sciences',
                'description' => 'Includes majors in religion and human sciences.',
                'abbreviation' => 'FRH'

            ],
            [ //here we have a problem with the abbreviation
                'name' => 'Department of Translation and Languages Department',
                'description' => 'Includes majors in arts, literature, and languages.',
                'abbreviation' => 'FRH'
            ],
        ];

        foreach ($faculties as $faculty) {
            Faculty::create($faculty);
        }
    }

}
