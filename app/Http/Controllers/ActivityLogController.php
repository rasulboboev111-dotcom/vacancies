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
     * Foreign-key columns that are stored as ids in the activity log but
     * should be shown as human-readable names. Maps column => [model, display].
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
     * Human-readable Tajik labels for the technical column names that show
     * up in the activity log. Anything not listed falls back to a prettified
     * version of the raw column name.
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
        'title' => 'Номи вакансия',
        'requirements' => 'Талабот',
        'schedule' => 'Ҷадвали корӣ',
        'salary' => 'Музди меҳнат',
        'description' => 'Тавсиф',
        'status' => 'Ҳолат',
        'opened_at' => 'Санаи кушодашавӣ',
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
     * Per-request cache of resolved lookup names, keyed by "model:id".
     *
     * @var array<string, ?string>
     */
    private array $lookupCache = [];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('view-audit-logs');

        $base = Activity::with('causer')->latest()->latest('id');

        // Branch users (non-admins) only ever see log entries for subjects in
        // their own branch — defence in depth, independent of who holds the
        // 'view audit logs' permission.
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
                    'created_at' => $log->created_at->format('d.m.Y H:i:s'),
                ];
            });

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->input('filter', []),
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    /**
     * Purge the entire audit trail. Admin-only: clearing the log is a
     * destructive, non-recoverable action, so it is gated more strictly than
     * merely viewing the log (which branch users with the permission may do).
     */
    public function clear(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        Activity::query()->delete();

        return back()->with('success', 'Сабти амалҳо пурра тоза карда шуд.');
    }

    /**
     * Replace raw foreign-key ids and enum codes inside the logged
     * properties with human-readable values, while preserving the
     * original structure ({ attributes: {...}, old: {...} }).
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

            // Relabel each technical column name to a readable label and
            // humanize its value. Both sections use the same key => label
            // mapping, so attributes and old stay aligned on the frontend.
            $relabeled = [];
            foreach ($props[$section] as $key => $value) {
                $label = $this->fieldLabel($key);
                // Two distinct columns can map to the same label (e.g.
                // nationality_id and nationality). Disambiguate so neither
                // value is silently overwritten.
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
     * Map a technical column name to its readable label, falling back to a
     * prettified version (drop trailing _id, underscores to spaces).
     */
    private function fieldLabel(string $key): string
    {
        if (isset(self::FIELD_LABELS[$key])) {
            return self::FIELD_LABELS[$key];
        }

        return ucfirst(str_replace('_', ' ', preg_replace('/_id$/', '', $key)));
    }

    /**
     * Resolve a single logged value to its human-readable form.
     */
    private function humanizeValue(string $key, $value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // Some historical log entries stored accessor-shaped values, e.g.
        // employment_type as {id, name}. Never try to coerce a non-scalar;
        // surface its name when available, otherwise leave it untouched.
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

        // Format ISO date/datetime values as a plain dd.mm.yyyy date.
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}([ T]|$)/', $value)) {
            try {
                return Carbon::parse($value)->format('d.m.Y');
            } catch (\Throwable $e) {
                // Fall through and return the raw value if parsing fails.
            }
        }

        return $value;
    }

    /**
     * Look up a referenced record's display value, caching per request and
     * including soft-deleted rows so historical references stay readable.
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
