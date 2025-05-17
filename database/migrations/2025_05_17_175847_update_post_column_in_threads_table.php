<?php
// database/migrations/xxxx_xx_xx_update_post_column_in_threads_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePostColumnInThreadsTable extends Migration
{
    public function up()
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->longText('post')->change();
        });
    }

    public function down()
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->text('post')->change(); // Optional, if you want to revert
        });
    }
}
