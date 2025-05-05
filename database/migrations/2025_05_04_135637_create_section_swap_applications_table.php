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
        Schema::create('section_swap_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('current_section');
            $table->string('desired_section');
            $table->string('current_day');
            $table->time('current_time');
            $table->string('desired_day');
            $table->time('desired_time');
            $table->text('reason')->nullable();
            $table->enum('status', ['open', 'accepted', 'cancelled', 'fulfilled'])->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_swap_applications');
    }
};
