<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppUserActivationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_user_activations', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->bigInteger('user_id');
            
            // A unique indentifier that should identify the user's installation
            // PER DEVICE. Expected to be a SHA1 sum.
            $table->char('identifier', 40);
            
            // The name of the device from which the user has activated an install.
            // This is optional.
            $table->text('device_id');
            
            $table->timestamps();
            
            // The user ID and the identifier should uniquely identify this record.
            $table->index(['user_id', 'identifier']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_user_activations');
    }
}
