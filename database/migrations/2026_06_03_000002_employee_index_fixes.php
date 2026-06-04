<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SIN is a government-issued unique identifier, just like INN and the
        // passport number — give it the same soft-delete-aware partial unique
        // index (NULL/empty values may repeat).
        //
        // Pre-flight: surface any pre-existing duplicates with a clear message
        // instead of a raw "could not create unique index" Postgres error that
        // would abort the migration without telling the operator what collides.
        $duplicates = DB::table('employees')
            ->whereNotNull('sin')
            ->where('sin', '<>', '')
            ->whereNull('deleted_at')
            ->groupBy('sin')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('sin');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot add unique index on employees.sin — duplicate values exist for: '.
                $duplicates->implode(', ').'. Resolve these before migrating.'
            );
        }

        DB::statement("CREATE UNIQUE INDEX employees_sin_unique ON employees (sin) WHERE sin IS NOT NULL AND sin <> '' AND deleted_at IS NULL");

        // Drop the redundant standalone branch_id index: the composite
        // (branch_id, dismissal_date) index already serves branch-only lookups
        // via its leftmost column, so this one was pure write overhead.
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index('branch_id');
        });

        DB::statement('DROP INDEX IF EXISTS employees_sin_unique');
    }
};
