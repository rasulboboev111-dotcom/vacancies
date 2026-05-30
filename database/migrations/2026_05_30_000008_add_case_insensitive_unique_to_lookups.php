<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lookup tables that should enforce case-insensitive uniqueness on name,
     * mirroring the positions table.
     *
     * @var string[]
     */
    private array $tables = ['nationalities', 'educations', 'specialties', 'birth_places'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            // Replace the plain unique index with a LOWER(TRIM(name)) one.
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropUnique($table . '_name_unique');
            });

            DB::statement("CREATE UNIQUE INDEX {$table}_name_lower_unique ON {$table} (LOWER(TRIM(name)))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            DB::statement("DROP INDEX IF EXISTS {$table}_name_lower_unique");

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unique('name');
            });
        }
    }
};
