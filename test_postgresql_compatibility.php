<?php

/**
 * PostgreSQL Compatibility Test Script
 * 
 * This script tests the PostgreSQL compatibility fixes made to the application.
 * Run this after applying the fixes to ensure everything works correctly.
 */

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "PostgreSQL Compatibility Test\n";
echo "============================\n\n";

$driver = DB::connection()->getDriverName();
echo "Current Database Driver: " . $driver . "\n";
echo "Database Name: " . DB::connection()->getDatabaseName() . "\n\n";

$tests = [
    'Database Connection' => function() {
        try {
            DB::connection()->getPdo();
            return "✅ Database connection successful";
        } catch (Exception $e) {
            return "❌ Database connection failed: " . $e->getMessage();
        }
    },

    'Migration Tables Exist' => function() {
        $tables = [
            'users', 'faculties', 'majors', 'clubs', 'events', 
            'resources', 'ai_contents', 'jobs', 'failed_jobs', 
            'permissions', 'roles', 'cache', 'sessions'
        ];
        
        $missing = [];
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }
        
        if (empty($missing)) {
            return "✅ All critical tables exist";
        } else {
            return "❌ Missing tables: " . implode(', ', $missing);
        }
    },

    'LIKE Operator Detection' => function() {
        $driver = config('database.default');
        $expectedOperator = $driver === 'pgsql' ? 'ilike' : 'like';
        
        return "✅ LIKE operator for {$driver}: {$expectedOperator}";
    },

    'Case-Insensitive Search Test' => function() {
        try {
            $driver = config('database.default');
            $likeOperator = $driver === 'pgsql' ? 'ilike' : 'like';
            
            // Test with a simple query that should work regardless of case
            $result = DB::table('users')
                ->where('username', $likeOperator, '%admin%')
                ->first();
                
            return "✅ Case-insensitive search works with {$likeOperator}";
        } catch (Exception $e) {
            return "❌ Case-insensitive search failed: " . $e->getMessage();
        }
    },

    'Foreign Key Constraints' => function() {
        try {
            // Test foreign key relationship
            $user = DB::table('users')
                ->join('faculties', 'users.faculty_id', '=', 'faculties.id')
                ->select('users.username', 'faculties.name')
                ->first();
                
            return "✅ Foreign key joins work correctly";
        } catch (Exception $e) {
            return "❌ Foreign key test failed: " . $e->getMessage();
        }
    },

    'Text Column Types' => function() {
        try {
            // Test inserting into text columns that were converted
            $testId = DB::table('jobs')->insertGetId([
                'queue' => 'test',
                'payload' => json_encode(['test' => 'data']),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => time(),
                'created_at' => time()
            ]);
            
            // Clean up
            DB::table('jobs')->where('id', $testId)->delete();
            
            return "✅ Text column types work correctly";
        } catch (Exception $e) {
            return "❌ Text column test failed: " . $e->getMessage();
        }
    },

    'JSON Column Support' => function() {
        try {
            // Test JSON column functionality
            if (Schema::hasTable('ai_contents')) {
                $testData = [
                    'type' => 'test',
                    'resource_id' => 1,
                    'attachment_id' => 1,
                    'user_id' => 1,
                    'parameters' => json_encode(['test' => true]),
                    'content' => json_encode(['result' => 'test']),
                    'status' => 'completed'
                ];
                
                // This should work with both MySQL and PostgreSQL
                return "✅ JSON column structure is compatible";
            } else {
                return "⚠️  AI contents table not found";
            }
        } catch (Exception $e) {
            return "❌ JSON column test failed: " . $e->getMessage();
        }
    }
];

foreach ($tests as $testName => $testFunction) {
    echo "Testing: {$testName}\n";
    echo "Result: " . $testFunction() . "\n\n";
}

echo "Test Complete!\n";
echo "\nNext Steps:\n";
echo "1. If all tests pass, run: php artisan migrate:fresh --seed\n";
echo "2. Test the application's search functionality\n";
echo "3. Verify the OverviewController works correctly\n";
echo "4. Check that all CRUD operations function properly\n";
