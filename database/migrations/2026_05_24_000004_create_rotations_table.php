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
            $table->string('old_position');
            $table->string('new_position');
            $table->string('old_structure');
            $table->string('new_structure');
            $table->date('rotation_date');
            $table->string('reason')->nullable();
            $table->timestamps();
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
