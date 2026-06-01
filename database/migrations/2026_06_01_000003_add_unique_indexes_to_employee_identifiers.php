<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * INN (tax id) and passport number identify a person and must not be
     * shared by two different active employees. Use partial unique indexes so
     * the constraint ignores NULL/empty values and soft-deleted rows — this
     * keeps re-entry after deletion possible and mirrors the soft-delete-aware
     * unique indexes already used on departments/positions.
     */
    public function up(): void
    {
        DB::statement("
            CREATE UNIQUE INDEX employees_inn_unique
            ON employees (inn)
            WHERE inn IS NOT NULL AND inn <> '' AND deleted_at IS NULL
        ");

        DB::statement("
            CREATE UNIQUE INDEX employees_passport_number_unique
            ON employees (passport_number)
            WHERE passport_number IS NOT NULL AND passport_number <> '' AND deleted_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS employees_inn_unique');
        DB::statement('DROP INDEX IF EXISTS employees_passport_number_unique');
    }
};
