<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            // Bot's application.id — idempotency key. Nullable: manual entries have none.
            // Уникальность задаётся ниже частичным индексом (только для активных строк).
            $table->unsignedBigInteger('external_id')->nullable();
            // Branch may be unresolved on intake (admin triages); manual entries set it.
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('vacancy_id')->nullable()->constrained('vacancies')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('vacancy_title')->nullable();   // raw title string from the bot
            $table->string('source', 32)->nullable();      // telegram / email / somon / manual
            $table->text('summary')->nullable();
            $table->json('survey')->nullable();
            // Резюме хранятся через spatie/laravel-medialibrary (коллекция 'resumes').
            $table->timestamp('source_created_at')->nullable(); // created_at from the bot
            $table->timestamps();
            $table->softDeletes();
            $table->index('branch_id');
        });

        // Уникальность external_id только среди активных (не удалённых) заявок:
        // плоский unique учитывал бы soft-deleted строки, из-за чего повторный
        // приём от бота по тому же external_id после удаления падал бы на нём.
        DB::statement('CREATE UNIQUE INDEX applications_external_id_active_unique ON applications (external_id) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
