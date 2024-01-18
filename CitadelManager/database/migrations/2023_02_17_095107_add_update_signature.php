<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpdateSignature extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('system_versions', function (Blueprint $table) {
            $table->string('alpha_ed_signature')->nullable();
            $table->string('beta_ed_signature')->nullable();
            $table->string('stable_ed_signature')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('system_versions', function ($table) {
            $table->dropColumn('alpha_ed_signature');
            $table->dropColumn('beta_ed_signature');
            $table->dropColumn('stable_ed_signature');
        });
    }
}
