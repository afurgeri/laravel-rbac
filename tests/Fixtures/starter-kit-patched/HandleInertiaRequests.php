<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;
use Modules\Rbac\Contracts\HasPermissions;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'permissions' => $request->user() instanceof HasPermissions
                    ? Inertia::once(fn (): array => $request->user()->permissionNames())
                        ->as('permissions.'.$request->user()->getAuthIdentifier())
                    : [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
