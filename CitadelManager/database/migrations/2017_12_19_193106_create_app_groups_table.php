<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::create('app_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('group_name');
            $table->timestamps();
        });
        Schema::create('apps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('notes', 255);
            $table->timestamps();
        });
        Schema::create('app_group_to_apps', function (Blueprint $table) {
            $table->bigInteger('app_group_id')->unsigned();
            $table->bigInteger('app_id')->unsigned();
            $table->foreign('app_group_id')->references('id')->on('app_groups');
            $table->foreign('app_id')->references('id')->on('apps');
        });
        Schema::create('user_group_to_app_groups', function (Blueprint $table) {
            $table->bigInteger('user_group_id')->unsigned();
            $table->bigInteger('app_group_id')->unsigned();
            $table->foreign('user_group_id')->references('id')->on('groups');
            $table->foreign('app_group_id')->references('id')->on('app_groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_groups');
        Schema::dropIfExists('apps');
        Schema::dropIfExists('user_group_to_app_groups');
        Schema::dropIfExists('app_group_to_apps');
    }
}
