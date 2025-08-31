<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test the optimized query
echo "Testing optimized topContributors query...\n";

try {
    $startTime = microtime(true);
    
    $topContributors = \App\Models\User::query()
        ->select([
            'users.id',
            'users.username',
            'users.first_name',
            'users.last_name',
            'users.email',
            'users.avatar',
            'users.faculty_id',
            'users.major_id',
            'faculties.name as faculty_name',
            'majors.name as major_name',
            DB::raw('COUNT(DISTINCT resources.id) as resources_count'),
            DB::raw('COUNT(DISTINCT user_upvotes.id) as user_upvote_count'),
            DB::raw('COUNT(DISTINCT resource_upvotes.id) as resource_upvote_count'),
            DB::raw('(COUNT(DISTINCT resources.id) + COUNT(DISTINCT user_upvotes.id) + COUNT(DISTINCT resource_upvotes.id)) as contribution_score')
        ])
        ->leftJoin('faculties', 'users.faculty_id', '=', 'faculties.id')
        ->leftJoin('majors', 'users.major_id', '=', 'majors.id')
        ->leftJoin('resources', 'users.id', '=', 'resources.user_id')
        ->leftJoin('upvotes as user_upvotes', function ($join) {
            $join->on('users.id', '=', 'user_upvotes.upvoteable_id')
                 ->where('user_upvotes.upvoteable_type', '=', \App\Models\User::class);
        })
        ->leftJoin('upvotes as resource_upvotes', function ($join) {
            $join->on('resources.id', '=', 'resource_upvotes.upvoteable_id')
                 ->where('resource_upvotes.upvoteable_type', '=', \App\Models\Resource::class);
        })
        ->groupBy([
            'users.id',
            'users.username', 
            'users.first_name',
            'users.last_name',
            'users.email',
            'users.avatar',
            'users.faculty_id',
            'users.major_id',
            'faculties.name',
            'majors.name'
        ])
        ->having('resources_count', '>', 0)
        ->orderByDesc('contribution_score')
        ->orderByDesc('resources_count')
        ->limit(5)
        ->get();

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

    echo "Query executed successfully!\n";
    echo "Execution time: " . round($executionTime, 2) . " ms\n";
    echo "Results found: " . $topContributors->count() . "\n";
    
    if ($topContributors->count() > 0) {
        echo "\nTop contributors:\n";
        foreach ($topContributors as $contributor) {
            echo "- {$contributor->username}: {$contributor->resources_count} resources, score: {$contributor->contribution_score}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
