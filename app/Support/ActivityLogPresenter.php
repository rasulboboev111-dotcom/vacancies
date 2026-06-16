<?php

namespace App\Support;

use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\OrgStatus;
use App\Enums\VacancyEmploymentType;
use App\Enums\VacancyStatus;
use App\Models\BirthPlace;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Education;
use App\Models\Employee;
use App\Models\Nationality;
use App\Models\Position;
use App\Models\Specialty;
use App\Models\User;
use App\Models\Vacancy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Презентация записи журнала действий для фронтенда: человекочитаемые подписи
 * полей, разрешение FK-id в имена, метки enum и имя затронутой записи. Вынесено
 * из ActivityLogController, чтобы контроллер отвечал только за доступ/листинг, а
 * правила отображения жили в одном месте (SRP). Инстанс живёт в пределах одного
 * запроса — `$lookupCache` мемоизирует разрешения id→имя.
 */
class ActivityLogPresenter
{
    /**
     * Столбцы внешних ключей: column => [model, display]. Хранятся как id, но
     * показываются именем.
     */
    private const FK_RESOLVERS = [
        'branch_id' => [Branch::class, 'name'],
        'department_id' => [Department::class, 'name'],
        'position_id' => [Position::class, 'name'],
        'manager_id' => [Employee::class, 'full_name'],
        'nationality_id' => [Nationality::class, 'name'],
        'education_id' => [Education::class, 'name'],
        'specialty_id' => [Specialty::class, 'name'],
        'birth_place_id' => [BirthPlace::class, 'name'],
        'created_by' => [User::class, 'name'],
        // Rotation
        'employee_id' => [Employee::class, 'full_name'],
        'old_branch_id' => [Branch::class, 'name'],
        'new_branch_id' => [Branch::class, 'name'],
        'old_position_id' => [Position::class, 'name'],
        'new_position_id' => [Position::class, 'name'],
        'old_department_id' => [Department::class, 'name'],
        'new_department_id' => [Department::class, 'name'],
    ];

    /**
     * Таджикские/русские подписи технических имён столбцов. Чего нет — откатывается
     * к приукрашенной версии имени столбца.
     */
    private const FIELD_LABELS = [
        // Employee
        'branch_id' => 'Филиал',
        'department_id' => 'Шуъба',
        'category' => 'Категория',
        'position_id' => 'Вазифа',
        'structure_id' => 'Сохтор',
        'manager_id' => 'Роҳбари бевосита',
        'employment_type' => 'Намуди шуғл',
        'full_name' => 'Ному насаб',
        'gender' => 'Ҷинс',
        'hire_date' => 'Санаи қабул',
        'dismissal_date' => 'Санаи озодшавӣ',
        'dismissal_reason' => 'Сабаби озодшавӣ',
        'email' => 'Почтаи электронӣ',
        'birth_date' => 'Санаи таваллуд',
        'nationality_id' => 'Миллат',
        'nationality' => 'Миллат',
        'passport_number' => 'Рақами шиноснома',
        'passport_start_date' => 'Шиноснома: санаи додашуда',
        'passport_end_date' => 'Шиноснома: эътибор то',
        'passport_issued_by' => 'Шиноснома: аз ҷониби',
        'inn' => 'РМА (ИНН)',
        'sin' => 'РСШ (SIN)',
        'address' => 'Суроғаи истиқомат',
        'phone_number' => 'Рақами телефон',
        'birth_place_id' => 'Зодгоҳ',
        'birth_place' => 'Зодгоҳ',
        'education_id' => 'Маълумот',
        'education' => 'Маълумот',
        'specialty_id' => 'Ихтисос',
        'specialty' => 'Ихтисос',
        'employment_start_date' => 'Оғози фаъолияти меҳнатӣ',
        // Vacancy
        'location' => 'Место деятельности',
        'openings' => 'Количество вакансий',
        'supervisor' => 'Непосредственный руководитель',
        'experience' => 'Опыт работы',
        'skills' => 'Ключевые навыки',
        'requirements' => 'Дополнительные требования',
        'responsibilities' => 'Основные обязанности',
        'schedule_type' => 'График работы',
        'schedule_other' => 'Иной график',
        'work_format' => 'Формат работы',
        'salary' => 'Уровень дохода',
        'probation' => 'Испытательный срок',
        'probation_other' => 'Иной испытательный срок',
        'opening_reason' => 'Причина открытия позиции',
        'priority' => 'Приоритет',
        'status' => 'Ҳолат',
        'opened_at' => 'Дата подачи заявки',
        'deadline' => 'Планируемая дата закрытия',
        'closed_at' => 'Санаи пӯшидашавӣ',
        'created_by' => 'Эҷодкунанда',
        // Application
        'phone' => 'Телефон',
        'vacancy_title' => 'Вакансия',
        'source' => 'Источник',
        'summary' => 'Краткое описание',
        // Branch / Department
        'name' => 'Ном',
        'code' => 'Рамз',
        'parent_id' => 'Шуъбаи болоӣ',
        // Rotation
        'rotation_date' => 'Санаи ротатсия',
        'reason' => 'Сабаб',
        'employee_id' => 'Корманд',
        'old_branch_id' => 'Филиали қаблӣ',
        'new_branch_id' => 'Филиали нав',
        'old_position_id' => 'Вазифаи қаблӣ',
        'new_position_id' => 'Вазифаи нав',
        'old_department_id' => 'Шуъбаи қаблӣ',
        'new_department_id' => 'Шуъбаи нав',
    ];

    /**
     * Кэш разрешённых имён в пределах запроса, по ключу "model:id".
     *
     * @var array<string, ?string>
     */
    private array $lookupCache = [];

    /**
     * Готовая к выводу строка журнала.
     *
     * @return array<string, mixed>
     */
    public function present(Activity $log): array
    {
        return [
            'id' => $log->id,
            'description' => $log->description,
            'subject_type' => class_basename($log->subject_type),
            'subject_label' => $this->subjectLabel($log),
            'event' => $log->event,
            'causer_name' => $log->causer instanceof User ? $log->causer->name : 'Низом',
            'properties' => $this->humanizeProperties($log->properties, $log->subject_type),
            'created_at' => $log->created_at?->format('d.m.Y H:i:s'),
        ];
    }

    /**
     * Заменяет сырые id внешних ключей и коды enum внутри записанных свойств
     * человекочитаемыми значениями, сохраняя структуру { attributes, old }.
     *
     * @param  class-string|null  $subjectType
     * @return array<string, mixed>
     */
    private function humanizeProperties($properties, ?string $subjectType = null): array
    {
        $props = $properties instanceof Collection
            ? $properties->toArray()
            : (array) $properties;

        foreach (['attributes', 'old'] as $section) {
            if (! isset($props[$section]) || ! is_array($props[$section])) {
                continue;
            }

            $relabeled = [];
            foreach ($props[$section] as $key => $value) {
                $label = $this->fieldLabel($key);
                // Два столбца могут давать одну подпись (nationality_id и
                // nationality) — делаем её однозначной, чтобы значение не затёрлось.
                if (array_key_exists($label, $relabeled)) {
                    $label = "{$label} ({$key})";
                }
                $relabeled[$label] = $this->humanizeValue($key, $value, $subjectType);
            }
            $props[$section] = $relabeled;
        }

        return $props;
    }

    private function fieldLabel(string $key): string
    {
        if (isset(self::FIELD_LABELS[$key])) {
            return self::FIELD_LABELS[$key];
        }

        return ucfirst(str_replace('_', ' ', preg_replace('/_id$/', '', $key)));
    }

    /**
     * Имя затронутой записи (чтобы было видно, ЧТО создано/изменено/удалено, в т.ч.
     * когда запись уже удалена). Из сырых свойств: прямые «именные» поля, иначе —
     * название по position_id/employee_id.
     */
    private function subjectLabel(Activity $log): ?string
    {
        $props = $log->properties instanceof Collection
            ? $log->properties->toArray()
            : (array) $log->properties;

        $data = $props['attributes'] ?? $props['old'] ?? [];
        if (! is_array($data)) {
            return null;
        }

        foreach (['full_name', 'name', 'vacancy_title'] as $key) {
            if (isset($data[$key]) && is_scalar($data[$key]) && $data[$key] !== '') {
                return (string) $data[$key];
            }
        }

        // Вакансия не хранит собственного имени — берём название должности.
        if (! empty($data['position_id'])) {
            return $this->lookupName(Position::class, 'name', $data['position_id']);
        }

        // Ротация — по сотруднику.
        if (! empty($data['employee_id'])) {
            return $this->lookupName(Employee::class, 'full_name', $data['employee_id']);
        }

        return null;
    }

    /**
     * Приводит одно записанное значение к человекочитаемому виду.
     *
     * @param  class-string|null  $subjectType
     */
    private function humanizeValue(string $key, $value, ?string $subjectType = null)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // Исторические записи могли хранить значение как {id, name}. Нескалярное
        // не разрешаем — показываем name, если есть.
        if (! is_scalar($value)) {
            return is_array($value) ? ($value['name'] ?? $value) : $value;
        }

        if (isset(self::FK_RESOLVERS[$key])) {
            [$model, $display] = self::FK_RESOLVERS[$key];

            return $this->lookupName($model, $display, $value) ?? $value;
        }

        if ($key === 'employment_type') {
            // У вакансии и сотрудника разные наборы типов занятости — выбираем
            // enum по типу затронутой записи, иначе метка не разрешится.
            $enum = is_a($subjectType, Vacancy::class, true)
                ? VacancyEmploymentType::class
                : EmploymentType::class;

            return $enum::tryFrom((string) $value)?->label() ?? $value;
        }

        if ($key === 'gender') {
            return Gender::tryFrom((string) $value)?->label() ?? $value;
        }

        // Статус — через enum (вакансия: open/closed; орг-сущности: Active/Inactive),
        // чтобы метки не дублировались строками здесь.
        if ($key === 'status') {
            return VacancyStatus::tryFrom((string) $value)?->label()
                ?? OrgStatus::tryFrom((string) $value)?->label()
                ?? $value;
        }

        // ISO дата/время → dd.mm.yyyy.
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}([ T]|$)/', $value)) {
            try {
                return Carbon::parse($value)->format('d.m.Y');
            } catch (\Throwable $e) {
                // Не удалось разобрать — возвращаем сырое значение ниже.
            }
        }

        return $value;
    }

    /**
     * Имя связанной записи, кэшируется в пределах запроса; включает мягко
     * удалённые строки, чтобы исторические ссылки оставались читаемыми.
     */
    private function lookupName(string $model, string $display, $id): ?string
    {
        $cacheKey = $model.':'.$id;

        if (! array_key_exists($cacheKey, $this->lookupCache)) {
            $query = $model::query();

            if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                $query->withTrashed();
            }

            $this->lookupCache[$cacheKey] = $query->find($id)?->{$display};
        }

        return $this->lookupCache[$cacheKey];
    }
}
