<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterStatusEnumOnApplicationsTable extends Migration
{
    public function up()
    {
        // For SQLite, you may need to use raw SQL or recreate the column
        Schema::table('applications', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'declined', 'cancelled'])
                  ->default('pending')
                  ->change();
        });
    }

    public function down()
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'declined'])
                  ->default('pending')
                  ->change();
        });
    }
}