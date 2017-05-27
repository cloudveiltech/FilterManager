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
 * This table manages groups, to which users and filtering data can be assigned.
 * Users that belong to a group are able to pull data assigned to that group,
 * and use it in-application client side.
 */
class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // Name of the group.
            $table->string('name');
            
            // The app config for users in this group.
            $table->json('app_cfg');
            
            // The sha1 sum of the group's current data payload.
            $table->char('data_sha1', 40);
            
            // Whether or not the group is activated.
            $table->boolean('isactive');
            
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
        Schema::dropIfExists('groups');
    }
}
