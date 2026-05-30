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
        // Normalize any free-text employment_type values to the canonical
        // enum values (App\Enums\EmploymentType).
        DB::table('employees')
            ->whereIn('employment_type', ['Штатный', 'штатный', 'Штатӣ', 'штатӣ', 'Full-time', 'full-time'])
            ->update(['employment_type' => 'штатный']);

        DB::table('employees')
            ->whereIn('employment_type', ['Контракт', 'контракт', 'Шартномавӣ', 'шартномавӣ', 'Contract', 'contract'])
            ->update(['employment_type' => 'контракт']);

        // Coerce any remaining unrecognized value to the default so the CHECK
        // constraint below can never fail to apply on an existing dataset.
        DB::table('employees')
            ->whereNotIn('employment_type', ['штатный', 'контракт'])
            ->update(['employment_type' => 'штатный']);

        // Enforce the two allowed values at the database level.
        DB::statement("ALTER TABLE employees ADD CONSTRAINT employees_employment_type_check CHECK (employment_type IN ('штатный', 'контракт'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE employees DROP CONSTRAINT IF EXISTS employees_employment_type_check');
    }
};
