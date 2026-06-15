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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->nullable()->index();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tin', 20)->nullable();
            $table->unsignedBigInteger('ceo_external_id')->nullable();
            $table->unsignedBigInteger('head_company_external_id')->nullable();
            $table->string('status')->nullable();
            $table->string('code', 50);
            $table->string('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('deleted_at');
        });

        // Уникальный ключ импорта с учётом soft-delete (NULL и удалённые строки исключены).
        DB::statement('CREATE UNIQUE INDEX branches_external_id_unique ON branches (external_id) WHERE external_id IS NOT NULL AND deleted_at IS NULL');

        // Уникальный code с учётом soft-delete: code удалённого филиала можно переиспользовать.
        DB::statement('CREATE UNIQUE INDEX branches_code_unique ON branches (code) WHERE deleted_at IS NULL');

        // Enum статуса проверяется на уровне БД (App\Enums\OrgStatus); NULL проходит.
        DB::statement("ALTER TABLE branches ADD CONSTRAINT branches_status_check CHECK (status IN ('Active', 'Inactive'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
