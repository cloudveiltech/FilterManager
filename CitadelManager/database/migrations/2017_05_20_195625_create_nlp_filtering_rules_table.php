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
 * This table stores data for text content filtering "rules." In this case,
 * the rules are full binary payloads of Apache OpenNLP doccat classification
 * models.
 */
class CreateNlpFilteringRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nlp_filtering_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // The ID of the parent filtering list.
            $table->bigInteger('filter_list_id');
            
            // The sha1 sum of this list entry.
            $table->char('sha1', 40);
            
            // The binary payload of this list. In this case, it's an
            // Apache OpenNLP doccat model.
            $table->binary('data');
            
            // Created at/modified at.
            $table->timestamps();
            
            // The list ID and the sum should uniquely identify this entry.
            $table->unique(['filter_list_id', 'sha1']);
        });
        
        // Laravel doesn't support medium and long blob. Unfortunately,
        // this syntax binds us to a MySQL backend.
        DB::statement("ALTER TABLE nlp_filtering_rules MODIFY data MEDIUMBLOB");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nlp_filtering_rules');
    }
}
