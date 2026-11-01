<?php

namespace Modules\Rbac\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Log out users that were deactivated while they had an open session.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && method_exists($user, 'isDeactivated') && $user->isDeactivated()) {
            Auth::guard()->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson() || ! Route::has('login')) {
                abort(403, __('Your account has been deactivated.'));
            }

            return redirect()->route('login')->withErrors([
                (string) config('fortify.username', 'email') => __('Your account has been deactivated.'),
            ]);
        }

        return $next($request);
    }
}
