<?php

namespace App\Http\Controllers;

use App\Data\ApplicationData;
use App\Http\Requests\Application\StoreApplicationRequest;
use App\Http\Requests\Application\UpdateApplicationRequest;
use App\Models\Application;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Application::class);

        $user = $request->user();

        $base = Application::query()
            ->with(['branch:id,name,code', 'vacancy:id,title'])
            ->viewableBy($user)
            ->latest()
            ->latest('id');

        $applications = QueryBuilder::for($base)
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('source'),
            ])
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Application $a) => ApplicationData::from($a));

        return Inertia::render('Applications/Index', [
            'applications' => $applications,
            'filters' => $request->input('filter', []),
            'branches' => $user->isAdmin() ? Branch::orderBy('name')->get(['id', 'name']) : [],
            'sources' => ['telegram', 'email', 'somon', 'manual'],
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $application = new Application($request->safe()->except('resume'));
        $application->save();
        $this->storeResume($request, $application);

        return back()->with('success', 'Ариза илова шуд');
    }

    public function update(UpdateApplicationRequest $request, int $id): RedirectResponse
    {
        $application = Application::findOrFail($id);
        $application->update($request->safe()->except('resume'));
        $this->storeResume($request, $application);

        return back()->with('success', 'Ариза навсозӣ шуд');
    }

    public function destroy(int $id): RedirectResponse
    {
        $application = Application::findOrFail($id);
        Gate::authorize('delete', $application);
        $application->delete();

        return back()->with('success', 'Ариза нест карда шуд');
    }

    public function downloadResume(int $id): StreamedResponse
    {
        $application = Application::findOrFail($id);
        Gate::authorize('view', $application);

        abort_unless(
            $application->resume_path && Storage::disk(config('intake.disk'))->exists($application->resume_path),
            404
        );

        return Storage::disk(config('intake.disk'))->download(
            $application->resume_path,
            $application->resume_filename
        );
    }

    private function storeResume(Request $request, Application $application): void
    {
        if (! $request->hasFile('resume')) {
            return;
        }

        $file = $request->file('resume');
        $ext = $file->getClientOriginalExtension() ?: 'bin';
        $path = $file->storeAs('resumes', 'app_'.$application->id.'.'.$ext, config('intake.disk'));

        $application->update([
            'resume_path' => $path,
            'resume_filename' => $file->getClientOriginalName(),
        ]);
    }
}
