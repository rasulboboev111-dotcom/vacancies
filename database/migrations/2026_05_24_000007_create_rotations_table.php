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
        Schema::create('rotations', function (Blueprint $table) {
            $table->id();
            // Rotations are audit/history: a movement keeps meaning even after
            // the referenced employee or branch is purged, so these are nullable
            // nullOnDelete (a force-delete must not destroy the history).
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('old_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('new_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('old_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('new_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('old_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('new_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->date('rotation_date');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('old_branch_id');
            $table->index('new_branch_id');
            $table->index('old_position_id');
            $table->index('new_position_id');
            $table->index('old_department_id');
            $table->index('new_department_id');
            $table->index('rotation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rotations');
    }
};
