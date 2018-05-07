<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsAppUserActivationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_user_activations', function (Blueprint $table) {
            $table->integer('check_in_days')->default(0);
            $table->tinyInteger('alert_partner')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_user_activations', function (Blueprint $table) {
            $table->dropColumn('check_in_days');
            $table->dropColumn('alert_partner');
        });
    }
}
