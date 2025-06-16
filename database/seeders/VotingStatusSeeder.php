<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VotingStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'voting_status'],
            [
                'value' => 'closed',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
