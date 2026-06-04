<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enforce the employment-type value set on vacancies too (NULL passes),
        // matching the employees table.
        DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_employment_type_check CHECK (employment_type IN ('штатный', 'контракт'))");

        // PostgreSQL does not auto-index the referencing side of a foreign key;
        // departments.manager_id is joined/filtered, so index it explicitly.
        Schema::table('departments', function (Blueprint $table) {
            $table->index('manager_id');
        });

        // Drop the plain B-tree index on full_name: it cannot serve the
        // `LIKE '%term%'` search (scopeSearch) and is therefore dead weight.
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['full_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE vacancies DROP CONSTRAINT IF EXISTS vacancies_employment_type_check');

        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['manager_id']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->index('full_name');
        });
    }
};
