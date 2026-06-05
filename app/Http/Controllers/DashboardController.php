<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\VacancyStatus;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Display the dashboard statistics.
     */
    public function index(): Response
    {
        $user = auth()->user();

        // Total stats. Employee figures count only active (non-dismissed) staff —
        // archived/dismissed employees (dismissal_date set) are excluded.
        $totalEmployeesCount = Employee::query()->whereNull('dismissal_date')->viewableBy($user)->count();
        $totalBranchesCount = Branch::query()->viewableBy($user)->count();

        // Branch distribution (active employees only)
        $branchStats = Branch::withCount(['employees' => fn ($q) => $q->whereNull('dismissal_date')])
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

        // Category distribution. Aggregates run on the query builder (not
        // Eloquent) so the COALESCE fallback isn't fed through the category
        // enum cast, and no Employee $with/$appends are hydrated per row.
        $categoryStats = DB::table('employees')
            ->whereNull('deleted_at')
            ->whereNull('dismissal_date')
            ->select(DB::raw("COALESCE(category, 'Зикр нашудааст') as category"), DB::raw('count(*) as count'))
            ->viewableByBranch($user)
            ->groupBy(DB::raw("COALESCE(category, 'Зикр нашудааст')"))->get();

        // Type distribution
        $typeStats = DB::table('employees')
            ->whereNull('deleted_at')
            ->whereNull('dismissal_date')
            ->select('employment_type as type', DB::raw('count(*) as count'))
            ->viewableByBranch($user)
            ->groupBy('employment_type')
            ->get()
            ->map(function ($stat) {
                $enum = EmploymentType::tryFrom($stat->type);

                return [
                    'type' => $enum ? $enum->label() : 'Зикр нашудааст',
                    'count' => $stat->count,
                ];
            });

        // Gender distribution — overall and among managers. A manager is an
        // employee whose category is "Роҳбарият", the same rule that backs
        // Employee::is_manager. Aggregated on the query builder so no Employee
        // appends/relations are hydrated per row.
        $tallyGender = function (bool $onlyManagers) use ($user) {
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
        };

        $genderStats = $tallyGender(false);
        $managerGenderStats = $tallyGender(true);

        // Vacancy stats
        $openVacanciesCount = Vacancy::query()->where('status', VacancyStatus::OPEN->value)
            ->viewableBy($user)->count();
        $closedVacanciesCount = Vacancy::query()->where('status', VacancyStatus::CLOSED->value)
            ->viewableBy($user)->count();

        // Open vacancies broken down by branch
        $vacancyByBranch = Branch::query()
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

        // Recent activity logs
        $recentActivitiesQuery = Activity::with('causer');
        Employee::restrictActivitiesTo($recentActivitiesQuery, $user);

        $recentActivities = $recentActivitiesQuery->latest()
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

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_employees' => $totalEmployeesCount,
                'total_branches' => $totalBranchesCount,
                'branch_stats' => $branchStats,
                'category_stats' => $categoryStats,
                'type_stats' => $typeStats,
                'gender_stats' => $genderStats,
                'manager_gender_stats' => $managerGenderStats,
                'open_vacancies' => $openVacanciesCount,
                'closed_vacancies' => $closedVacanciesCount,
                'vacancy_by_branch' => $vacancyByBranch,
            ],
            'recent_activities' => $recentActivities,
        ]);
    }
}
