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
     * Rotation rows are audit/history data: a movement keeps meaning (old/new
     * branch, position, department, date) even after the referenced employee or
     * branch is purged. So a force-delete must NOT destroy the history. Switch
     * employee_id (CASCADE) and new_branch_id (RESTRICT, non-nullable) to
     * nullable nullOnDelete references — matching old_branch_id — and add the
     * missing indexes on the department foreign keys.
     */
    public function up(): void
    {
        Schema::table('rotations', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['new_branch_id']);
        });

        Schema::table('rotations', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->change();
            $table->unsignedBigInteger('new_branch_id')->nullable()->change();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('new_branch_id')->references('id')->on('branches')->nullOnDelete();

            $table->index('old_department_id');
            $table->index('new_department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting to NOT NULL is impossible once a purge has nulled these out.
        // Fail loudly with a clear reason instead of a cryptic Postgres error
        // mid-rollback (which would leave the table half-reverted).
        $orphans = DB::table('rotations')
            ->whereNull('employee_id')
            ->orWhereNull('new_branch_id')
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException(
                "Cannot reverse: {$orphans} rotation row(s) have a NULL employee_id/new_branch_id ".
                '(history of purged employees/branches). Resolve or delete them before rolling back.'
            );
        }

        Schema::table('rotations', function (Blueprint $table) {
            $table->dropIndex(['old_department_id']);
            $table->dropIndex(['new_department_id']);
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['new_branch_id']);
        });

        Schema::table('rotations', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();
            $table->unsignedBigInteger('new_branch_id')->nullable(false)->change();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('new_branch_id')->references('id')->on('branches')->restrictOnDelete();
        });
    }
};
