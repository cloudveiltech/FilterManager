<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropFilterListImportsTable extends Migration
{
    /**
     * Remove the superseded shared import-tracking state.
     */
    public function up()
    {
        Schema::dropIfExists('filter_list_imports');
    }

    /**
     * Restore the table if this migration is rolled back.
     */
    public function down()
    {
        Schema::create('filter_list_imports', function (Blueprint $table) {
            $table->increments('id');
            $table->string('disk', 64);
            $table->string('file');
            $table->string('category', 64);
            $table->string('etag', 128)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamp('object_last_modified')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique(['disk', 'file']);
        });
    }
}
