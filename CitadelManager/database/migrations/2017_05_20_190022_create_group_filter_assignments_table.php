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
 * This table manages the assignment of filter lists to groups.
 */
class CreateGroupFilterAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_filter_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // The group that the below filter list should be assigned to.
            $table->bigInteger('group_id');
            
            // The filter list ID that should be assigned to the above group.
            $table->bigInteger('filter_list_id');
            
            // Whether the assigned filter list is assigned as a blacklist.
            $table->boolean('as_blacklist');
            
            // Whether the assigned filter list is assigned as a whitelist.
            $table->boolean('as_whitelist');
            
            // Whether the assigned filter list is assigned as a bypassable
            // blacklist. These lists can potentially be permitted to operate
            // as a whitelist for a fixed time for a fixed number of uses
            // per day.
            $table->boolean('as_bypass');
            
            // Created at/modified at.
            $table->timestamps();
            
            // The group ID, the filter list ID and how it is assigned should 
            // uniquely identify this entry.
            $table->unique(['group_id', 'filter_list_id', 'as_blacklist', 'as_whitelist', 'as_bypass'], 'idx_unique_filter_assignment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_filter_assignments');
    }
}
