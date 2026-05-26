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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('type_id')->nullable()->constrained('employment_types')->nullOnDelete();
            $table->string('full_name');
            $table->string('gender');
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('structure_id')->nullable()->constrained('structures')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('hire_date');
            $table->date('dismissal_date')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_start_date')->nullable();
            $table->date('passport_end_date')->nullable();
            $table->string('passport_issued_by')->nullable();
            $table->string('inn')->nullable();
            $table->string('sin')->nullable();
            $table->string('address')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('education')->nullable();
            $table->string('specialty')->nullable();
            $table->date('employment_start_date')->nullable();
            $table->timestamps();

            // Explicit indexes for Postgres optimization
            $table->index('branch_id');
            $table->index('category_id');
            $table->index('type_id');
            $table->index('position_id');
            $table->index('structure_id');
            $table->index('manager_id');
            $table->index('full_name');
            $table->index('dismissal_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
