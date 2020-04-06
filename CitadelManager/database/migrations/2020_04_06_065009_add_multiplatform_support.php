<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMultiplatformSupport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_user_activations', function (Blueprint $table) {
            $table->string('platform_name')->nullable(false)->index();
        });
        DB::statement("UPDATE app_user_activations SET platform_name='WIN'");
        DB::statement("UPDATE app_user_activations SET platform_name='OSX' WHERE device_id LIKE '%Mac%'");

        Schema::table('apps', function (Blueprint $table) {
            $table->string('platform_name')->nullable(false)->index();
        });

        DB::statement("UPDATE apps SET platform_name='WIN'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_user_activations', function (Blueprint $table) {
            $table->dropColumn('platform_name');
        });

        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn('platform_name');
        });
    }
}
