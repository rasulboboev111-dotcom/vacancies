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
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('old_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('new_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('old_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('new_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('old_structure_id')->nullable()->constrained('structures')->nullOnDelete();
            $table->foreignId('new_structure_id')->nullable()->constrained('structures')->nullOnDelete();
            $table->date('rotation_date');
            $table->string('reason')->nullable();
            $table->timestamps();

            // Explicit indexes for PostgreSQL performance
            $table->index('employee_id');
            $table->index('old_branch_id');
            $table->index('new_branch_id');
            $table->index('old_position_id');
            $table->index('new_position_id');
            $table->index('old_structure_id');
            $table->index('new_structure_id');
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
