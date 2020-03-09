<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEntriesCountAttribute extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('filter_lists', function (Blueprint $table) {
            $table->integer('entries_count')->nullable(false);
        });
        DB::statement("UPDATE filter_lists SET entries_count=(SELECT count(1) FROM text_filtering_rules WHERE filter_list_id=filter_lists.id)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('filter_lists', function (Blueprint $table) {
            $table->dropColumn('entries_count');
        });
    }
}
