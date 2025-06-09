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
        Schema::create('study_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('capacity')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_online')->default(false);
            $table->boolean('is_complete')->default(false);
            $table->dateTime('meeting_time');

            // Foreign keys
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('major_id')->constrained()->onDelete('cascade');
            $table->foreignId('faculty_id')->constrained()->onDelete('cascade');

            $table->timestamps();
        });

        // Pivot table for members
        Schema::create('study_group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_groups');
    }
};
