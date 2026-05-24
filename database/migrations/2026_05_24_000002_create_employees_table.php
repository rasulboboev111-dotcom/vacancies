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
            $table->string('category');
            $table->string('type');
            $table->string('full_name');
            $table->string('position');
            $table->string('structure');
            $table->string('direct_manager')->nullable();
            $table->date('hire_date');
            $table->date('dismissal_date')->nullable();
            $table->string('passport_issued_by')->nullable();
            $table->string('inn')->nullable();
            $table->timestamps();
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
