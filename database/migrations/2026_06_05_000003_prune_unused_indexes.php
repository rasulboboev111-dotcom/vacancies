<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Гигиена индексов по итогам ревью БД:
     *
     * - Удаляем обычные b-tree индексы, дублирующие частичный UNIQUE-индекс, уже
     *   обслуживающий поиск по равенству (inn, sin, external_id). Unique
     *   покрывает `WHERE col = ?` для живых строк, поэтому обычный индекс — чистый
     *   оверхед на запись.
     * - Удаляем низкоселективные индексы внешних ключей-справочников на employees,
     *   которые никогда не используются в фильтрах WHERE/JOIN (nationality/
     *   education/specialty/birth_place); Postgres не требует их для самого FK.
     * - Удаляем индексы внешних ключей rotations, по которым никогда не
     *   фильтруют (экран фильтрует только по филиалу); rotations — почти
     *   append-only таблица аудита.
     * - Заменяем отдельный индекс departments.sort_order составным
     *   (branch_id, sort_order), так как сортировка всегда в рамках филиала.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['inn']);            // employees_inn_index (оставлен: employees_inn_unique)
            $table->dropIndex(['sin']);            // employees_sin_index (оставлен: employees_sin_unique)
            $table->dropIndex(['external_id']);    // employees_external_id_index (оставлен: *_unique)
            $table->dropIndex(['nationality_id']);
            $table->dropIndex(['education_id']);
            $table->dropIndex(['specialty_id']);
            $table->dropIndex(['birth_place_id']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex(['external_id']);    // branches_external_id_index (оставлен: *_unique)
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['external_id']);    // departments_external_id_index (оставлен: *_unique)
            $table->dropIndex(['sort_order']);
            $table->index(['branch_id', 'sort_order']);
        });

        Schema::table('rotations', function (Blueprint $table) {
            $table->dropIndex(['old_position_id']);
            $table->dropIndex(['new_position_id']);
            $table->dropIndex(['old_department_id']);
            $table->dropIndex(['new_department_id']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index('inn');
            $table->index('sin');
            $table->index('external_id');
            $table->index('nationality_id');
            $table->index('education_id');
            $table->index('specialty_id');
            $table->index('birth_place_id');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->index('external_id');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'sort_order']);
            $table->index('external_id');
            $table->index('sort_order');
        });

        Schema::table('rotations', function (Blueprint $table) {
            $table->index('old_position_id');
            $table->index('new_position_id');
            $table->index('old_department_id');
            $table->index('new_department_id');
        });
    }
};
