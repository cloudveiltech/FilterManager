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
 * This table serves as a sort of index of all filtering rules. Rules, whether
 * they be a single text line URL filter, or an Apache OpenNLP doccat model,
 * or an ICE visual vocabulary model for image classification, are stored
 * in separate tables, one entry per "rule". This table serves as the main
 * index of all such entries, as all rule entries are mapped back to a
 * "parent" filter_list entry.
 */
class CreateFilterListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('filter_lists', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // The namespace for this list entry. Namespace enables us to have
            // more than one category of the same name, within different
            // namespaces of course. It also enabled us to have multiple
            // NLP and visual classifier models, because without namespaces,
            // only 1 of each of those models would be permitted on the server
            // at a time. The reason why only 1 would be allowed at a time
            // is because these lists enter "NLP" or "VISUAL" for both the
            // category name and type.
            $table->string('namespace', 64);
            
            // The category name for the rules contained in this "list".
            $table->string('category', 64);
            
            // The type of filter rules contained in this "list".
            $table->string('type', 32);
            
            // Created at/modified at.
            $table->timestamps();
            
            // The namespace, category name and the type should all serve to
            // uniquely identify an entry.
            $table->unique(['namespace', 'category', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('filter_lists');
    }
}
