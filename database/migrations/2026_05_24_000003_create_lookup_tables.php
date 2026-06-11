<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Справочные словари, на которые ссылаются employees/vacancies. Справочники,
     * выведенные из свободного текста, и positions требуют регистронезависимый
     * (LOWER(TRIM(name))) unique, чтобы повторять логику резолвера. (Категория
     * сотрудника — фиксированный enum, а не справочная таблица, см.
     * App\Enums\Category.)
     */
    public function up(): void
    {
        foreach (['nationalities', 'educations', 'specialties', 'birth_places'] as $lookup) {
            Schema::create($lookup, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
            DB::statement("CREATE UNIQUE INDEX {$lookup}_name_lower_unique ON {$lookup} (LOWER(TRIM(name)))");
        }

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('external_id')->nullable()->unique();
            $table->timestamps();
        });
        DB::statement('CREATE UNIQUE INDEX positions_name_lower_unique ON positions (LOWER(TRIM(name)))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['positions', 'birth_places', 'specialties', 'educations', 'nationalities'] as $lookup) {
            Schema::dropIfExists($lookup);
        }
    }
};
