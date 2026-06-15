<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Плоский unique(external_id) учитывает и мягко удалённые строки, поэтому
     * повторный приём от бота по тому же external_id после soft-delete падал на
     * нарушении уникальности. Заменяем на частичный уникальный индекс, который
     * действует только для активных (не удалённых) заявок — идемпотентность бота
     * сохраняется, а удалённые дубликаты не блокируют новый приём.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
        });

        DB::statement('CREATE UNIQUE INDEX applications_external_id_active_unique ON applications (external_id) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX applications_external_id_active_unique');

        Schema::table('applications', function (Blueprint $table) {
            $table->unique('external_id');
        });
    }
};
