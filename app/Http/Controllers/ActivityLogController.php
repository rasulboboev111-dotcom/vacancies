<?php

namespace App\Http\Controllers;

use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Models\BirthPlace;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Education;
use App\Models\Employee;
use App\Models\Nationality;
use App\Models\Position;
use App\Models\Specialty;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityLogController extends Controller
{
    /**
     * Столбцы внешних ключей, которые хранятся в журнале как id, но должны
     * отображаться человекочитаемыми именами. Карта column => [model, display].
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
    ];

    /**
     * Человекочитаемые таджикские подписи для технических имён столбцов,
     * которые появляются в журнале действий. Всё, чего нет в списке,
     * откатывается к приукрашенной версии исходного имени столбца.
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
        // Branch / Department
        'name' => 'Ном',
        'code' => 'Рамз',
        'parent_id' => 'Шуъбаи болоӣ',
        // Rotation
        'rotation_date' => 'Санаи ротатсия',
        'reason' => 'Сабаб',
    ];

    /**
     * Кэш разрешённых имён в пределах запроса, по ключу "model:id".
     *
     * @var array<string, ?string>
     */
    private array $lookupCache = [];

    public function index(Request $request): Response
    {
        Gate::authorize('view-audit-logs');

        $base = Activity::with('causer')->latest()->latest('id');

        // Пользователи филиала (не админы) всегда видят только записи журнала
        // по субъектам своего филиала — эшелонированная защита, независимая от
        // того, у кого есть право 'view audit logs'.
        $user = $request->user();
        Employee::restrictActivitiesTo($base, $user);

        $logs = QueryBuilder::for($base)
            ->allowedFilters([
                AllowedFilter::partial('search', 'description'),
                AllowedFilter::exact('event'),
            ])
            ->paginate(15)->withQueryString()->through(function ($log) {
                return [
                    'id' => $log->id,
                    'description' => $log->description,
                    'subject_type' => class_basename($log->subject_type),
                    'event' => $log->event,
                    'causer_name' => $log->causer ? $log->causer->name : 'Низом',
                    'properties' => $this->humanizeProperties($log->properties),
                    'created_at' => $log->created_at?->format('d.m.Y H:i:s'),
                ];
            });

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->input('filter', []),
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    /**
     * Полностью очищает журнал аудита. Только для админов: очистка журнала —
     * разрушительное, необратимое действие, поэтому ограничено строже, чем
     * простой просмотр журнала (доступный пользователям филиала с правом).
     */
    public function clear(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        Activity::query()->delete();

        return back()->with('success', 'Сабти амалҳо пурра тоза карда шуд.');
    }

    /**
     * Заменяет сырые id внешних ключей и коды enum внутри записанных свойств
     * человекочитаемыми значениями, сохраняя исходную структуру
     * ({ attributes: {...}, old: {...} }).
     */
    private function humanizeProperties($properties): array
    {
        $props = $properties instanceof Collection
            ? $properties->toArray()
            : (array) $properties;

        foreach (['attributes', 'old'] as $section) {
            if (! isset($props[$section]) || ! is_array($props[$section])) {
                continue;
            }

            // Переименовываем каждое техническое имя столбца в читаемую подпись
            // и приводим его значение к читаемому виду. Обе секции используют
            // одну карту key => label, поэтому attributes и old остаются
            // согласованными на фронтенде.
            $relabeled = [];
            foreach ($props[$section] as $key => $value) {
                $label = $this->fieldLabel($key);
                // Два разных столбца могут давать одинаковую подпись (например,
                // nationality_id и nationality). Делаем её однозначной, чтобы ни
                // одно значение не было молча перезаписано.
                if (array_key_exists($label, $relabeled)) {
                    $label = "{$label} ({$key})";
                }
                $relabeled[$label] = $this->humanizeValue($key, $value);
            }
            $props[$section] = $relabeled;
        }

        return $props;
    }

    /**
     * Сопоставляет техническое имя столбца его читаемой подписи, откатываясь к
     * приукрашенной версии (убрать завершающий _id, подчёркивания в пробелы).
     */
    private function fieldLabel(string $key): string
    {
        if (isset(self::FIELD_LABELS[$key])) {
            return self::FIELD_LABELS[$key];
        }

        return ucfirst(str_replace('_', ' ', preg_replace('/_id$/', '', $key)));
    }

    /**
     * Приводит одно записанное значение к человекочитаемому виду.
     */
    private function humanizeValue(string $key, $value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // Некоторые исторические записи журнала хранили значения в форме
        // аксессора, например employment_type как {id, name}. Никогда не
        // приводим нескалярное значение; показываем его name, если доступно,
        // иначе оставляем как есть.
        if (! is_scalar($value)) {
            return is_array($value) ? ($value['name'] ?? $value) : $value;
        }

        if (isset(self::FK_RESOLVERS[$key])) {
            [$model, $display] = self::FK_RESOLVERS[$key];

            return $this->lookupName($model, $display, $value) ?? $value;
        }

        if ($key === 'employment_type') {
            return EmploymentType::tryFrom((string) $value)?->label() ?? $value;
        }

        if ($key === 'gender') {
            return Gender::tryFrom((string) $value)?->label() ?? $value;
        }

        if ($key === 'status') {
            return match ((string) $value) {
                'open' => 'Кушода',
                'closed' => 'Пӯшида',
                'Active' => 'Фаъол',
                'Inactive' => 'Ғайрифаъол',
                default => $value,
            };
        }

        // Форматируем значения ISO даты/времени как обычную дату dd.mm.yyyy.
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}([ T]|$)/', $value)) {
            try {
                return Carbon::parse($value)->format('d.m.Y');
            } catch (\Throwable $e) {
                // При ошибке разбора проваливаемся дальше и возвращаем сырое значение.
            }
        }

        return $value;
    }

    /**
     * Ищет отображаемое значение связанной записи, кэшируя в пределах запроса
     * и включая мягко удалённые строки, чтобы исторические ссылки оставались
     * читаемыми.
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
