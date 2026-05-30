<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PostgreSQL does not auto-create indexes on foreign-key columns, so we
     * add them explicitly for every FK that is filtered or joined on.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // department_id was added without an index.
            $table->index('department_id');

            // Newly normalized lookup FKs.
            $table->index('nationality_id');
            $table->index('education_id');
            $table->index('specialty_id');
            $table->index('birth_place_id');

            // The employee list is always scoped to a branch and split by
            // active (dismissal_date IS NULL) vs. archived.
            $table->index(['branch_id', 'dismissal_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'dismissal_date']);
            $table->dropIndex(['birth_place_id']);
            $table->dropIndex(['specialty_id']);
            $table->dropIndex(['education_id']);
            $table->dropIndex(['nationality_id']);
            $table->dropIndex(['department_id']);
        });
    }
};
