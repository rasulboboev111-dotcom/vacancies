<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index('deleted_at');
            $table->index('branch_id');
            $table->index('category_id');
            $table->index('employment_type');
            $table->index('position_id');
            $table->index('structure_id');
            $table->index('manager_id');
            $table->index('inn');
            $table->index('sin');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('deleted_at');
            $table->index('branch_id');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('rotations', function (Blueprint $table) {
            $table->index('employee_id');
            $table->index('old_branch_id');
            $table->index('new_branch_id');
            $table->index('old_position_id');
            $table->index('new_position_id');
            $table->index('old_structure_id');
            $table->index('new_structure_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['employment_type']);
            $table->dropIndex(['position_id']);
            $table->dropIndex(['structure_id']);
            $table->dropIndex(['manager_id']);
            $table->dropIndex(['inn']);
            $table->dropIndex(['sin']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['branch_id']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('rotations', function (Blueprint $table) {
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['old_branch_id']);
            $table->dropIndex(['new_branch_id']);
            $table->dropIndex(['old_position_id']);
            $table->dropIndex(['new_position_id']);
            $table->dropIndex(['old_structure_id']);
            $table->dropIndex(['new_structure_id']);
        });
    }
};
