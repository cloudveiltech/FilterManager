<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilterListImportsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('filter_list_imports', function (Blueprint $table) {
            $table->increments('id');
            $table->string('disk', 64);
            $table->string('file');
            $table->string('category', 64);
            // The object's ETag and last modified time as reported by the disk,
            // so we can tell whether what we imported is still what the export
            // pipeline has in the bucket. Nothing is written back to the bucket
            // itself: CloudVeilManager reads the same objects during migration.
            $table->string('etag', 128)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamp('object_last_modified')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique(['disk', 'file']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('filter_list_imports');
    }
}
