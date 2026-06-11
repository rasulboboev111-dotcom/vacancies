<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Делаем vacancies.salary числовым столбцом вместо свободного varchar, чтобы
     * его можно было валидировать, сортировать и агрегировать как настоящее число.
     * Существующие значения — целые; пустые строки становятся NULL.
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
