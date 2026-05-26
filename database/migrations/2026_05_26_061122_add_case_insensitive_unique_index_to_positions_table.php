<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            // Drop the default unique index
            $table->dropUnique('positions_name_unique');
        });

        // Create a case-insensitive unique index using lower() and trim()
        // This is highly compatible with PgSQL
        DB::statement('CREATE UNIQUE INDEX positions_name_lower_unique ON positions (LOWER(TRIM(name)))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS positions_name_lower_unique');

        Schema::table('positions', function (Blueprint $table) {
            // Re-create the default unique index
            $table->unique('name');
        });
    }
};
