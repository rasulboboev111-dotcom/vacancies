<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
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

        // Total stats
        $totalEmployees = Employee::query();
        $totalBranches = Branch::query();

        if (!$user->hasRole('Admin') && $user->branch_id === null) {
            $totalEmployees->whereRaw('1=0');
            $totalBranches->whereRaw('1=0');
        }

        $totalEmployeesCount = $totalEmployees->count();
        $totalBranchesCount = $totalBranches->count();

        // Branch distribution
        $branchStatsQuery = Branch::withCount('employees');

        if (!$user->hasRole('Admin') && $user->branch_id === null) {
            $branchStatsQuery->whereRaw('1=0');
        }

        $branchStats = $branchStatsQuery->orderBy('name')->get()
            ->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'employees_count' => $branch->employees_count,
                ];
            });

        // Category distribution
        $categoryStatsQuery = Employee::leftJoin('categories', 'employees.category_id', '=', 'categories.id')
            ->select(DB::raw("COALESCE(categories.name, 'Не указано') as category"), DB::raw('count(*) as count'));

        if (!$user->hasRole('Admin') && $user->branch_id === null) {
            $categoryStatsQuery->whereRaw('1=0');
        }

        $categoryStats = $categoryStatsQuery->groupBy(DB::raw("COALESCE(categories.name, 'Не указано')"))->get();

        // Type distribution
        $typeStatsQuery = Employee::select('employment_type as type', DB::raw('count(*) as count'));

        if (!$user->hasRole('Admin') && $user->branch_id === null) {
            $typeStatsQuery->whereRaw('1=0');
        }

        $typeStats = $typeStatsQuery->groupBy('employment_type')
            ->get()
            ->map(function ($stat) {
                $enum = \App\Enums\EmploymentType::tryFrom($stat->type);
                return [
                    'type' => $enum ? $enum->label() : 'Не указано',
                    'count' => $stat->count,
                ];
            });

        // Recent activity logs
        $recentActivitiesQuery = Activity::with('causer');

        if (!$user->hasRole('Admin') && $user->branch_id === null) {
            $recentActivitiesQuery->whereRaw('1=0');
        }

        $recentActivities = $recentActivitiesQuery->latest()
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'subject_type' => class_basename($activity->subject_type),
                    'event' => $activity->event,
                    'causer_name' => $activity->causer ? $activity->causer->name : 'Система',
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
            ],
            'recent_activities' => $recentActivities,
        ]);
    }
}
