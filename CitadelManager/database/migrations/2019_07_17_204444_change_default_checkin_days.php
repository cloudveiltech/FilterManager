<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDefaultCheckinDays extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_user_activations', function (Blueprint $table) {
            $table->integer('check_in_days')->default(7)->change();
        });

        DB::table('app_user_activations')->where('check_in_days', 0)->update(['check_in_days' => 7]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_user_activations', function (Blueprint $table) {
            $table->integer('check_in_days')->default(0)->change();
        });

        DB::table('app_user_activations')->where('check_in_days', 7)->update(['check_in_days' => 0]);
    }
}
