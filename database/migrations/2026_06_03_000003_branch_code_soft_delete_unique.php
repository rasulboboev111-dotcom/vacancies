<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Replace the plain unique on branches.code with a soft-delete-aware partial
     * unique index so a soft-deleted branch's code can be reused (matching the
     * FormRequest's whereNull('deleted_at') rule and the external_id pattern).
     */
    public function up(): void
    {
        // Guard: surface pre-existing duplicate codes among live rows clearly,
        // instead of a raw "could not create unique index" failure.
        $duplicates = DB::table('branches')
            ->whereNull('deleted_at')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('code');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot add partial unique index on branches.code — duplicate live codes exist for: '.
                $duplicates->implode(', ').'. Resolve these before migrating.'
            );
        }

        Schema::table('branches', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        DB::statement('CREATE UNIQUE INDEX branches_code_unique ON branches (code) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS branches_code_unique');

        Schema::table('branches', function (Blueprint $table) {
            $table->unique('code');
        });
    }
};
