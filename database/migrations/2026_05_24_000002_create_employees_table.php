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
            $table->string('gender');
            $table->string('position');
            $table->string('structure');
            $table->string('direct_manager')->nullable();
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
            $table->string('total_experience')->nullable();
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
