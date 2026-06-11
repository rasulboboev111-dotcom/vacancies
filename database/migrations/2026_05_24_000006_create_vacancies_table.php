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
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title')->nullable();
            $table->unsignedInteger('openings')->default(1);
            $table->string('employment_type')->nullable();
            $table->text('requirements')->nullable();
            $table->string('schedule')->nullable();
            $table->string('salary')->nullable();
            $table->text('description')->nullable();

            $table->string('status', 20)->default('open');
            $table->date('opened_at')->nullable();
            $table->date('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index('department_id');
        });

        // Enum статуса проверяется на уровне БД (App\Enums\VacancyStatus).
        DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_status_check CHECK (status IN ('open', 'closed'))");

        // Набор значений типа занятости (NULL проходит), как в таблице employees.
        DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_employment_type_check CHECK (employment_type IN ('штатный', 'контракт'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};
