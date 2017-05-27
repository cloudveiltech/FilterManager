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
 * Creates the users table.
 */
class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // The name of the user. Required. Could be just a first name, 
            // a full name, or last name, whatever.
            $table->string('name');
            
            // The email address for the user.
            $table->string('email')->unique();
            
            // The user's hashed password.
            $table->string('password');
            
            // The group that the user is assigned to. By default, a new
            // user is not assigned to any group, so a value of -1 is used.
            $table->integer('group_id')->default(-1);
            
            // By default, a new user is not active. Must be manually enabled
            // by an administrator.
            $table->boolean('isactive')->default(false);
            
            // How many activations on how many unique systems the user is 
            // permitted to have. Think of this as the number of computers
            // you can activate a software license on in a traditional license.
            $table->integer('activations_allowed')->default(1);
            
            // The number of activations used.
            $table->integer('activations_used')->default(0);
            
            
            $table->rememberToken();
            
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
        Schema::dropIfExists('users');
    }
}
