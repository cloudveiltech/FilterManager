<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SetFileSha1ToOptional extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('filter_lists', function (Blueprint $table) {
            $table->string('file_sha1', 40)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('filter_lists', function (Blueprint $table) {
            $table->string('file_sha1', 40)->change();
        });
    }
}
