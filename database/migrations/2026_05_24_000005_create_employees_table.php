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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->nullable()->index();
            $table->unsignedBigInteger('person_id')->nullable()->index();

            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('nationality_id')->nullable()->constrained('nationalities')->nullOnDelete();
            $table->foreignId('education_id')->nullable()->constrained('educations')->nullOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained('specialties')->nullOnDelete();
            $table->foreignId('birth_place_id')->nullable()->constrained('birth_places')->nullOnDelete();

            $table->string('category')->nullable();
            $table->string('employment_type')->default('штатный');
            $table->string('full_name');
            $table->string('gender')->nullable();
            $table->string('email', 254)->nullable();
            $table->string('status')->nullable();
            $table->integer('sort_order')->default(0);

            $table->date('hire_date')->nullable();
            $table->date('dismissal_date')->nullable();
            $table->string('dismissal_reason')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('passport_number', 30)->nullable();
            $table->date('passport_start_date')->nullable();
            $table->date('passport_end_date')->nullable();
            $table->string('passport_issued_by')->nullable();
            $table->string('inn', 20)->nullable();
            $table->string('sin', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('phone_number', 32)->nullable();
            $table->date('employment_start_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'dismissal_date']);
            $table->index('position_id');
            $table->index('manager_id');
            $table->index('department_id');
            $table->index('nationality_id');
            $table->index('education_id');
            $table->index('specialty_id');
            $table->index('birth_place_id');
            $table->index('employment_type');
            $table->index('dismissal_date');
            $table->index('inn');
            $table->index('sin');
            $table->index('deleted_at');
        });

        // Наборы значений enum проверяются на уровне БД (App\Enums\*). NULL
        // проходит (UNKNOWN), поэтому nullable-столбцы enum остаются необязательными.
        DB::statement("ALTER TABLE employees ADD CONSTRAINT employees_gender_check CHECK (gender IN ('мужской', 'женский'))");
        DB::statement("ALTER TABLE employees ADD CONSTRAINT employees_employment_type_check CHECK (employment_type IN ('штатный', 'контракт'))");
        DB::statement("ALTER TABLE employees ADD CONSTRAINT employees_category_check CHECK (category IN ('Мутахассис', 'Коргар', 'Роҳбарият'))");
        DB::statement("ALTER TABLE employees ADD CONSTRAINT employees_status_check CHECK (status IN ('Active', 'Inactive'))");

        // Частичные unique-индексы с учётом soft-delete (NULL/пустое/удалённое исключены).
        DB::statement('CREATE UNIQUE INDEX employees_external_id_unique ON employees (external_id) WHERE external_id IS NOT NULL AND deleted_at IS NULL');
        DB::statement("CREATE UNIQUE INDEX employees_inn_unique ON employees (inn) WHERE inn IS NOT NULL AND inn <> '' AND deleted_at IS NULL");
        DB::statement("CREATE UNIQUE INDEX employees_sin_unique ON employees (sin) WHERE sin IS NOT NULL AND sin <> '' AND deleted_at IS NULL");
        DB::statement("CREATE UNIQUE INDEX employees_passport_number_unique ON employees (passport_number) WHERE passport_number IS NOT NULL AND passport_number <> '' AND deleted_at IS NULL");
        DB::statement("CREATE UNIQUE INDEX employees_email_unique ON employees (email) WHERE email IS NOT NULL AND email <> '' AND deleted_at IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
