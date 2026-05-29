<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normalize existing free-text gender values to the canonical enum values.
        DB::table('employees')
            ->whereIn('gender', ['Мужской', 'мужской', 'Male', 'male', 'M', 'м'])
            ->update(['gender' => 'мужской']);

        DB::table('employees')
            ->whereIn('gender', ['Женский', 'женский', 'Female', 'female', 'F', 'ж'])
            ->update(['gender' => 'женский']);

        // Enforce the two allowed values at the database level.
        DB::statement("ALTER TABLE employees ADD CONSTRAINT employees_gender_check CHECK (gender IN ('мужской', 'женский'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_gender_check');

        DB::table('employees')->where('gender', 'мужской')->update(['gender' => 'Мужской']);
        DB::table('employees')->where('gender', 'женский')->update(['gender' => 'Женский']);
    }
};
