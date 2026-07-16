<?php

namespace Modules\Rbac\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Rbac\Contracts\HasPermissions;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! $request->user() instanceof HasPermissions || ! $request->user()->hasPermission($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
