<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBypassFieldsToActivations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('app_user_activations', function (Blueprint $table) {
            $table->integer('bypass_quantity')->nullable();
            $table->integer('bypass_period')->nullable();
            $table->integer('bypass_used')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('app_user_activations', function (Blueprint $table) {
            $table->dropColumn('bypass_quantity');
            $table->dropColumn('bypass_period');
            $table->dropColumn('bypass_used');
        });
    }
}
