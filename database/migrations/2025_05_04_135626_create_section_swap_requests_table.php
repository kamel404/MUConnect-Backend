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
        Schema::create('section_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('exchange_requests')->onDelete('cascade');
            $table->foreignId('applicant_id')->constrained('students')->onDelete('cascade');
            $table->text('message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_swap_requests');
    }
};
