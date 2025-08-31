<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            // Index for resources.user_id - critical for contributor queries
            $table->index('user_id', 'idx_resources_user_id');
            // Composite index for filtering and sorting
            $table->index(['user_id', 'created_at'], 'idx_resources_user_created');
        });

        Schema::table('upvotes', function (Blueprint $table) {
            // Composite index for polymorphic upvotes - critical for performance
            $table->index(['upvoteable_type', 'upvoteable_id'], 'idx_upvotes_polymorphic');
            // Index for user upvotes lookup
            $table->index('user_id', 'idx_upvotes_user_id');
            // Composite index for user + polymorphic lookups
            $table->index(['user_id', 'upvoteable_type', 'upvoteable_id'], 'idx_upvotes_user_polymorphic');
        });

        Schema::table('users', function (Blueprint $table) {
            // Index for faculty and major joins
            $table->index('faculty_id', 'idx_users_faculty_id');
            $table->index('major_id', 'idx_users_major_id');
            // Composite index for common filtering
            $table->index(['faculty_id', 'major_id'], 'idx_users_faculty_major');
        });

        Schema::table('faculties', function (Blueprint $table) {
            // Index for name sorting/searching
            $table->index('name', 'idx_faculties_name');
        });

        Schema::table('majors', function (Blueprint $table) {
            // Index for name sorting/searching
            $table->index('name', 'idx_majors_name');
            // Index for faculty relationship
            if (Schema::hasColumn('majors', 'faculty_id')) {
                $table->index('faculty_id', 'idx_majors_faculty_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropIndex('idx_resources_user_id');
            $table->dropIndex('idx_resources_user_created');
        });

        Schema::table('upvotes', function (Blueprint $table) {
            $table->dropIndex('idx_upvotes_polymorphic');
            $table->dropIndex('idx_upvotes_user_id');
            $table->dropIndex('idx_upvotes_user_polymorphic');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_faculty_id');
            $table->dropIndex('idx_users_major_id');
            $table->dropIndex('idx_users_faculty_major');
        });

        Schema::table('faculties', function (Blueprint $table) {
            $table->dropIndex('idx_faculties_name');
        });

        Schema::table('majors', function (Blueprint $table) {
            $table->dropIndex('idx_majors_name');
            if (Schema::hasColumn('majors', 'faculty_id')) {
                $table->dropIndex('idx_majors_faculty_id');
            }
        });
    }
};
