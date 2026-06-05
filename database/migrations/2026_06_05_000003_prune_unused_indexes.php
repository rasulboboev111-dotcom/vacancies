<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index hygiene from the DB review:
     *
     * - Drop plain b-tree indexes that duplicate a partial UNIQUE index already
     *   serving equality lookups (inn, sin, external_id). The unique covers
     *   `WHERE col = ?` for live rows, so the plain index is pure write overhead.
     * - Drop low-selectivity lookup foreign-key indexes on employees that are
     *   never used in WHERE/JOIN filters (nationality/education/specialty/
     *   birth_place); Postgres does not require them for the FK itself.
     * - Drop rotation foreign-key indexes that are never filtered on (the screen
     *   filters by branch only); rotations is an append-mostly audit table.
     * - Replace the standalone departments.sort_order index with a
     *   (branch_id, sort_order) composite, since ordering is always scoped to a
     *   branch.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['inn']);            // employees_inn_index (kept: employees_inn_unique)
            $table->dropIndex(['sin']);            // employees_sin_index (kept: employees_sin_unique)
            $table->dropIndex(['external_id']);    // employees_external_id_index (kept: *_unique)
            $table->dropIndex(['nationality_id']);
            $table->dropIndex(['education_id']);
            $table->dropIndex(['specialty_id']);
            $table->dropIndex(['birth_place_id']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex(['external_id']);    // branches_external_id_index (kept: *_unique)
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['external_id']);    // departments_external_id_index (kept: *_unique)
            $table->dropIndex(['sort_order']);
            $table->index(['branch_id', 'sort_order']);
        });

        Schema::table('rotations', function (Blueprint $table) {
            $table->dropIndex(['old_position_id']);
            $table->dropIndex(['new_position_id']);
            $table->dropIndex(['old_department_id']);
            $table->dropIndex(['new_department_id']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index('inn');
            $table->index('sin');
            $table->index('external_id');
            $table->index('nationality_id');
            $table->index('education_id');
            $table->index('specialty_id');
            $table->index('birth_place_id');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->index('external_id');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'sort_order']);
            $table->index('external_id');
            $table->index('sort_order');
        });

        Schema::table('rotations', function (Blueprint $table) {
            $table->index('old_position_id');
            $table->index('new_position_id');
            $table->index('old_department_id');
            $table->index('new_department_id');
        });
    }
};
