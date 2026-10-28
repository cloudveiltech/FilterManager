<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categories that were denied by config before the flag moved into the
     * database. Kept here so the migration preserves the previous behaviour
     * rather than silently allowing them.
     */
    private const PREVIOUSLY_DENIED = ['Uncategorized', 'test', 'invalid'];

    public function up(): void
    {
        Schema::table('filter_lists', function (Blueprint $table) {
            $table->boolean('import_enabled')->default(true)->after('type');
        });

        DB::table('filter_lists')
            ->whereIn(DB::raw('LOWER(category)'), array_map('strtolower', self::PREVIOUSLY_DENIED))
            ->update(['import_enabled' => false]);
    }

    public function down(): void
    {
        Schema::table('filter_lists', function (Blueprint $table) {
            $table->dropColumn('import_enabled');
        });
    }
};
