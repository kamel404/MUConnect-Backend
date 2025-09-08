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
        Schema::create('ai_contents', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'quiz' or 'summary'
            $table->foreignId('resource_id');
            $table->foreignId('attachment_id');
            $table->foreignId('user_id');
            $table->json('parameters'); // Store the parameters used for generation
            $table->json('content')->nullable(); // Store the generated content
            $table->string('status')->default('processing'); // processing, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['resource_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index('status');

            $table->foreign('resource_id')->references('id')->on('resources')->onDelete('cascade');
            $table->foreign('attachment_id')->references('id')->on('attachments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_contents');
    }
};
