<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * И дашборд, и список журнала активности сортируют ленту по created_at при
     * каждой загрузке (Activity::latest() → ORDER BY created_at DESC), но в
     * созданной Spatie таблице нет индекса по этому столбцу. По мере роста лога
     * это вынуждает полное сканирование + сортировку. Добавляем недостающий
     * b-tree индекс.
     *
     * Использует config('activitylog.table_name'), чтобы нацелиться на ту таблицу,
     * которую создал пакет, а не хардкодить 'activity_log'.
     */
    public function up(): void
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
