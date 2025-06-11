<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachment_post', function (Blueprint $table) {
            // Drop existing foreign keys first
            $table->dropForeign(['post_id']);
            $table->dropForeign(['attachment_id']);

            // Re-add foreign keys WITHOUT cascade
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('restrict');
            $table->foreign('attachment_id')->references('id')->on('attachments')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('attachment_post', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
            $table->dropForeign(['attachment_id']);

            // Revert to cascade if rolling back
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('attachment_id')->references('id')->on('attachments')->onDelete('cascade');
        });
    }
};
