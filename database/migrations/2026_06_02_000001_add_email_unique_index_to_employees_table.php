<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Enforce a unique email per employee. First de-duplicate any pre-existing
     * values (keep the earliest id in each group, NULL the rest), then add a
     * soft-delete-aware partial unique index (NULL/empty/trashed exempt),
     * matching the existing inn/passport_number unique indexes. The dedupe is
     * idempotent: once applied, re-running is a no-op.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE employees
            SET email = NULL
            WHERE id IN (
                SELECT id FROM (
                    SELECT id, row_number() OVER (PARTITION BY email ORDER BY id) AS rn
                    FROM employees
                    WHERE email IS NOT NULL AND email <> '' AND deleted_at IS NULL
                ) ranked
                WHERE ranked.rn > 1
            )
        SQL);

        DB::statement("CREATE UNIQUE INDEX employees_email_unique ON employees (email) WHERE email IS NOT NULL AND email <> '' AND deleted_at IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS employees_email_unique');
    }
};
