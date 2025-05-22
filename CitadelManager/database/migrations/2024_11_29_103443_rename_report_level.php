<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameReportLevel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_user_activations', function (Blueprint $table) {
            $table->renameColumn('report_level', 'debug_enabled');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('report_level');
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
            $table->renameColumn('debug_enabled', 'report_level');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('report_level')->default(0);
        });
    }
}
