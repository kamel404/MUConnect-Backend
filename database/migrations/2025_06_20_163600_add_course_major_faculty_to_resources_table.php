<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->foreignId('faculty_id')->after('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->after('faculty_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->after('major_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_id');
            $table->dropConstrainedForeignId('major_id');
            $table->dropConstrainedForeignId('faculty_id');
        });
    }
};
