<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckInDaysToUsers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer("check_in_days")->nullable();
        });
        Schema::table('app_user_activations', function (Blueprint $table) {
            $table->integer("check_in_days")->default(null)->nullable()->change();
        });
        DB::statement("UPDATE app_user_activations SET check_in_days=NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn("check_in_days");
        });
        Schema::table('app_user_activations', function (Blueprint $table) {
            $table->integer("check_in_days")->default("7")->nullable(false)->change();
        });
        DB::statement("UPDATE app_user_activations SET check_in_days=7");
    }
};
