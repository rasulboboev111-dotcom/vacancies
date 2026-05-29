<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('view-audit-logs');

        $query = Activity::with('causer');

        // Search in log description
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('description', 'like', "%{$search}%");
        }

        // Filter by event type
        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        $logs = $query->latest()->paginate(15)->withQueryString()->through(function ($log) {
            return [
                'id' => $log->id,
                'description' => $log->description,
                'subject_type' => class_basename($log->subject_type),
                'event' => $log->event,
                'causer_name' => $log->causer ? $log->causer->name : 'Низом',
                'properties' => $log->properties,
                'created_at' => $log->created_at->format('d.m.Y H:i:s'),
            ];
        });

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'event']),
        ]);
    }
}
