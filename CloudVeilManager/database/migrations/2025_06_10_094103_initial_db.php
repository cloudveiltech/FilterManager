<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //DB::raw(Storage::get("initial_db.sql"));
        DB::raw("DELETE FROM TABLE migrations");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
