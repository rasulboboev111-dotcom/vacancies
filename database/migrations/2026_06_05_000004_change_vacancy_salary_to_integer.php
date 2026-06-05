<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make vacancies.salary a numeric column instead of free-text varchar, so it
     * can be validated, sorted and aggregated as a real number. Existing values
     * are whole numbers; empty strings become NULL.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE vacancies ALTER COLUMN salary TYPE integer USING NULLIF(TRIM(salary), '')::integer");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE vacancies ALTER COLUMN salary TYPE varchar(255) USING salary::varchar');
    }
};
