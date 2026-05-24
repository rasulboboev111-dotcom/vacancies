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
        // Total stats
        $totalEmployees = Employee::count();
        $totalBranches = Branch::count();

        // Branch distribution
        $branchStats = Branch::withCount('employees')
            ->get()
            ->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'employees_count' => $branch->employees_count,
                ];
            });

        // Category distribution
        $categoryStats = Employee::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get();

        // Type distribution
        $typeStats = Employee::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        // Recent activity logs
        $recentActivities = Activity::with('causer')
            ->latest()
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
                'total_employees' => $totalEmployees,
                'total_branches' => $totalBranches,
                'branch_stats' => $branchStats,
                'category_stats' => $categoryStats,
                'type_stats' => $typeStats,
            ],
            'recent_activities' => $recentActivities,
        ]);
    }
}
