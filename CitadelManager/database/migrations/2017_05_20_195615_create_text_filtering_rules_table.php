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

class CreateTextFilteringRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('text_filtering_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // The ID of the parent filtering list.
            $table->bigInteger('filter_list_id');
            
            // The sha1 sum of this list entry.
            $table->char('sha1', 40);
            
            // The raw text filtering rule.
            $table->text('rule');            
            
            // Created at/modified at.
            $table->timestamps();
            
            // The list ID and the sum should uniquely identify this entry.
            $table->unique(['filter_list_id', 'sha1']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('text_filtering_rules');
    }
}
