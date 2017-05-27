<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * This table stores requests from user's to deactivation the application
 * at a particular point of installation.
 */
class CreateDeactivationreqTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deactivation_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // The ID of the user making a deactivation request.
            $table->bigInteger('user_id');
            
            // A unique indentifier that should identify the user's installation
            // PER DEVICE. Expected to be a SHA1 sum.
            $table->char('identifier', 40);
            
            // The name of the device from which the user requested deactivation.
            // this is optional.
            $table->text('device_id');
            
            // Whether or not the request is granted.
            $table->boolean('granted')->default(false);
            
            // Created at/modified at.
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
        Schema::dropIfExists('deactivation_requests');
    }
}
