<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Нормализуем vacancies под официальный бланк «Заявка на подбор персонала»
     * (Приложение № 1 к СОП): дублирующий свободный title сворачивается в каталог
     * positions (position_id остаётся единственным полем «должность»), свободный
     * график разбивается на тип + текст «иной», чекбокс-группы бланка становятся
     * столбцами с CHECK-ограничениями, а языки переезжают в дочернюю таблицу
     * (по строке на язык).
     */
    public function up(): void
    {
        // Сворачиваем title в positions (регистронезависимый find-or-create), затем удаляем его.
        DB::statement(<<<'SQL'
            INSERT INTO positions (name, created_at, updated_at)
            SELECT DISTINCT ON (LOWER(TRIM(v.title))) TRIM(v.title), NOW(), NOW()
            FROM vacancies v
            WHERE v.position_id IS NULL AND NULLIF(TRIM(v.title), '') IS NOT NULL
            ORDER BY LOWER(TRIM(v.title))
            ON CONFLICT DO NOTHING
        SQL);
        DB::statement(<<<'SQL'
            UPDATE vacancies v
            SET position_id = p.id
            FROM positions p
            WHERE v.position_id IS NULL
              AND NULLIF(TRIM(v.title), '') IS NOT NULL
              AND LOWER(TRIM(p.name)) = LOWER(TRIM(v.title))
        SQL);

        // Тип занятости вакансии переходит на набор значений бланка («Тип занятости»).
        DB::statement('ALTER TABLE vacancies DROP CONSTRAINT IF EXISTS vacancies_employment_type_check');
        DB::statement("UPDATE vacancies SET employment_type = CASE employment_type WHEN 'штатный' THEN 'полная' WHEN 'контракт' THEN 'проектная' ELSE employment_type END");
        DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_employment_type_check CHECK (employment_type IN ('полная', 'частичная', 'проектная'))");

        Schema::table('vacancies', function (Blueprint $table) {
            $table->string('location')->nullable();
            $table->string('supervisor')->nullable();
            $table->string('education')->nullable();
            $table->string('experience')->nullable();
            $table->text('skills')->nullable();
            $table->string('schedule_type')->nullable();
            $table->string('schedule_other')->nullable();
            $table->string('work_format')->nullable();
            $table->string('probation')->nullable();
            $table->string('probation_other')->nullable();
            $table->string('opening_reason')->nullable();
            $table->string('priority')->nullable();
            $table->date('deadline')->nullable();
        });

        // Существующие свободные графики становятся опцией «Иной» с сохранением текста.
        DB::statement("UPDATE vacancies SET schedule_type = 'иной', schedule_other = TRIM(schedule) WHERE NULLIF(TRIM(schedule), '') IS NOT NULL");

        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn(['title', 'schedule']);
            $table->renameColumn('description', 'responsibilities');
        });

        // Чекбокс-группы проверяются на уровне БД (App\Enums\*).
        DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_education_check CHECK (education IN ('высшее', 'среднее специальное', 'не имеет значения'))");
        DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_experience_check CHECK (experience IN ('без опыта', 'от 1 года', 'от 3 лет и более'))");
        DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_schedule_type_check CHECK (schedule_type IN ('5/2', 'иной'))");
        DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_work_format_check CHECK (work_format IN ('офис', 'удалённо', 'гибрид'))");
        DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_probation_check CHECK (probation IN ('нет', '1 месяц', '3 месяца', 'иное'))");
        DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_opening_reason_check CHECK (opening_reason IN ('расширение штата', 'новая позиция', 'замена уволенного сотрудника', 'декретная ставка / временное замещение'))");
        DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_priority_check CHECK (priority IN ('низкая', 'средняя', 'высокая'))");

        // «Знание языков» — мультивыбор → по строке на язык.
        Schema::create('vacancy_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->constrained('vacancies')->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestamps();

            $table->unique(['vacancy_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancy_languages');

        foreach (['education', 'experience', 'schedule_type', 'work_format', 'probation', 'opening_reason', 'priority'] as $column) {
            DB::statement("ALTER TABLE vacancies DROP CONSTRAINT IF EXISTS vacancies_{$column}_check");
        }

        Schema::table('vacancies', function (Blueprint $table) {
            $table->renameColumn('responsibilities', 'description');
            $table->string('title')->nullable();
            $table->string('schedule')->nullable();
        });

        // Исходный свободный текст восстановим из каталога / поля «иной»;
        // удалённый title, у которого уже был position_id, остаётся привязанным к должности.
        DB::statement('UPDATE vacancies v SET title = p.name FROM positions p WHERE v.position_id = p.id');
        DB::statement('UPDATE vacancies SET schedule = COALESCE(schedule_other, schedule_type)');

        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn([
                'location', 'supervisor', 'education', 'experience', 'skills',
                'schedule_type', 'schedule_other', 'work_format', 'probation',
                'probation_other', 'opening_reason', 'priority', 'deadline',
            ]);
        });

        DB::statement('ALTER TABLE vacancies DROP CONSTRAINT IF EXISTS vacancies_employment_type_check');
        DB::statement("UPDATE vacancies SET employment_type = CASE employment_type WHEN 'полная' THEN 'штатный' WHEN 'частичная' THEN 'штатный' WHEN 'проектная' THEN 'контракт' ELSE employment_type END");
        DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_employment_type_check CHECK (employment_type IN ('штатный', 'контракт'))");
    }
};
