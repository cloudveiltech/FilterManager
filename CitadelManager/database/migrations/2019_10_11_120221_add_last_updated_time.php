<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLastUpdatedTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_user_activations', function (Blueprint $table) {
            $table->timestamp('last_update_requested_time')->nullable(false)->default(Carbon::parse('-365 days')->startOfDay())->index();
            $table->timestamp('last_sync_time')->nullable(false)->default(Carbon::parse('-365 days')->startOfDay())->index();
            $table->index('ip_address', 'ind_ip_address');
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
            $table->dropColumn('last_update_requested_time');
            $table->dropColumn('last_sync_time');
            $table->dropIndex('ind_ip_address');
        });
    }
}
