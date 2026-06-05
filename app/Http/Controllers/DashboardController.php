<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\VacancyStatus;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Display fallback for an employee whose category/type is unset.
     */
    private const NOT_SPECIFIED = 'Зикр нашудааст';

    /**
     * Display the dashboard statistics.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'stats' => [
                // Employee figures count only active (non-dismissed) staff —
                // archived/dismissed employees (dismissal_date set) are excluded.
                'total_employees' => Employee::query()->whereNull('dismissal_date')->viewableBy($user)->count(),
                'total_branches' => Branch::query()->viewableBy($user)->count(),
                'branch_stats' => $this->branchStats($user),
                'category_stats' => $this->categoryStats($user),
                'type_stats' => $this->typeStats($user),
                'gender_stats' => $this->tallyGender($user, false),
                'manager_gender_stats' => $this->tallyGender($user, true),
                'open_vacancies' => Vacancy::query()->where('status', VacancyStatus::OPEN->value)
                    ->viewableBy($user)->count(),
                'closed_vacancies' => Vacancy::query()->where('status', VacancyStatus::CLOSED->value)
                    ->viewableBy($user)->count(),
                'vacancy_by_branch' => $this->vacancyByBranch($user),
            ],
            'recent_activities' => $this->recentActivities($user),
        ]);
    }

    /**
     * Branch distribution (active employees only).
     */
    private function branchStats(User $user): Collection
    {
        return Branch::withCount(['employees' => fn ($q) => $q->whereNull('dismissal_date')])
            ->viewableBy($user)
            ->orderBy('name')->get()
            ->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'employees_count' => $branch->employees_count,
                ];
            });
    }

    /**
     * Category distribution. Aggregates run on the query builder (not Eloquent)
     * so the COALESCE fallback isn't fed through the category enum cast, and no
     * Employee $with/$appends are hydrated per row.
     */
    private function categoryStats(User $user): Collection
    {
        return DB::table('employees')
            ->whereNull('deleted_at')
            ->whereNull('dismissal_date')
            ->select(DB::raw("COALESCE(category, '".self::NOT_SPECIFIED."') as category"), DB::raw('count(*) as count'))
            ->viewableByBranch($user)
            ->groupBy(DB::raw("COALESCE(category, '".self::NOT_SPECIFIED."')"))->get();
    }

    /**
     * Type distribution.
     */
    private function typeStats(User $user): Collection
    {
        return DB::table('employees')
            ->whereNull('deleted_at')
            ->whereNull('dismissal_date')
            ->select('employment_type as type', DB::raw('count(*) as count'))
            ->viewableByBranch($user)
            ->groupBy('employment_type')
            ->get()
            ->map(function ($stat) {
                $enum = EmploymentType::tryFrom($stat->type);

                return [
                    'type' => $enum ? $enum->label() : self::NOT_SPECIFIED,
                    'count' => $stat->count,
                ];
            });
    }

    /**
     * Gender distribution — overall and among managers. A manager is an
     * employee whose category is "Роҳбарият", the same rule that backs
     * Employee::is_manager. Aggregated on the query builder so no Employee
     * appends/relations are hydrated per row.
     *
     * @return array{male: int, female: int, unspecified: int}
     */
    private function tallyGender(User $user, bool $onlyManagers): array
    {
        $query = DB::table('employees')->whereNull('deleted_at')->whereNull('dismissal_date')
            ->viewableByBranch($user);

        if ($onlyManagers) {
            $query->where('category', Category::MANAGEMENT->value);
        }

        $rows = $query->select('gender', DB::raw('count(*) as count'))
            ->groupBy('gender')->get();

        $tally = ['male' => 0, 'female' => 0, 'unspecified' => 0];
        foreach ($rows as $row) {
            if ($row->gender === Gender::MALE->value) {
                $tally['male'] += (int) $row->count;
            } elseif ($row->gender === Gender::FEMALE->value) {
                $tally['female'] += (int) $row->count;
            } else {
                $tally['unspecified'] += (int) $row->count;
            }
        }

        return $tally;
    }

    /**
     * Open/closed vacancies broken down by branch (branches with at least one).
     */
    private function vacancyByBranch(User $user): Collection
    {
        return Branch::query()
            ->withCount(['vacancies as open_vacancies_count' => function ($query) {
                $query->where('status', VacancyStatus::OPEN->value);
            }])
            ->withCount(['vacancies as closed_vacancies_count' => function ($query) {
                $query->where('status', VacancyStatus::CLOSED->value);
            }])
            ->viewableBy($user)
            ->orderBy('name')->get()
            ->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'open' => $branch->open_vacancies_count,
                'closed' => $branch->closed_vacancies_count,
            ])
            ->filter(fn ($branch) => $branch['open'] > 0 || $branch['closed'] > 0)
            ->values();
    }

    /**
     * Recent activity log entries, scoped to what the user may see.
     */
    private function recentActivities(User $user): Collection
    {
        $recentActivitiesQuery = Activity::with('causer');
        Employee::restrictActivitiesTo($recentActivitiesQuery, $user);

        return $recentActivitiesQuery->latest()
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'subject_type' => class_basename($activity->subject_type),
                    'event' => $activity->event,
                    'causer_name' => $activity->causer ? $activity->causer->name : 'Низом',
                    'properties' => $activity->properties,
                    'created_at' => $activity->created_at->format('d.m.Y H:i'),
                ];
            });
    }
}
