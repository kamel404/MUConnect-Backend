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

        // Course categories table - linked to majors
        Schema::create('course_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., "Core", "Elective", "Technical", etc.
            $table->foreignId('major_id')->unique()->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., "CS 301"
            $table->string('title');
            $table->integer('credits');
            $table->string('year')->nullable(); // e.g., "Year 1", "Year 2"
            $table->enum('semester', ['Fall', 'Spring', 'Summer'])->nullable();
            $table->foreignId('faculty_id')->constrained()->onDelete('cascade');
            $table->foreignId('major_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Course to category relationship (many-to-many)
        Schema::create('category_course', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_category_id')->constrained()->onDelete('cascade');
            $table->primary(['course_id', 'course_category_id']);
        });

        // Prerequisites relationship (self-referencing many-to-many)
        Schema::create('course_prerequisite', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('prerequisite_id')->references('id')->on('courses')->onDelete('cascade');
            $table->primary(['course_id', 'prerequisite_id']);
        });

        // User enrollments
        Schema::create('course_user', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['completed', 'in-progress', 'not-started'])->default('not-started');
            $table->primary(['course_id', 'user_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_course');
        Schema::dropIfExists('course_prerequisite');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('course_categories');
    }
};
