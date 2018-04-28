<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemVersionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_platforms', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('platform');
            $table->string('os_name');
        });
        Schema::create('system_versions', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('platform_id')->unsigned();
            $table->string('app_name');
            $table->string('file_name');
            $table->string('version_number');
            $table->text('changes');
            $table->string('alpha');
            $table->string('beta');
            $table->string('stable');
            $table->dateTime('release_date');
            $table->tinyInteger('active');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_versions');
        Schema::dropIfExists('system_platforms');
    }
}
