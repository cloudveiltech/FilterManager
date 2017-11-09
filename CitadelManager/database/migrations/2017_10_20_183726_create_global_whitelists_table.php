<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGlobalWhitelistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_whitelists', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // Name of the whitelist.
            $table->string('name');
            
            
            // The sha1 sum of the whitelist's current data payload.
            $table->char('data_sha1', 40)->default('');
            
            // Whether or not the whitelist is activated.
            $table->boolean('isactive')->default(true);
            
            // Created at/modified at.
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
        Schema::dropIfExists('global_whitelists');
    }
}
