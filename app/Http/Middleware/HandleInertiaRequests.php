<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Корневой шаблон, который загружается при первом посещении страницы.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Определяет текущую версию ассетов.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Задаёт свойства, разделяемые по умолчанию.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'branch_id' => $request->user()->branch_id,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
