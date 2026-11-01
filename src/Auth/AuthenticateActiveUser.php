<?php

namespace Modules\Rbac\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Login callback that rejects deactivated users once their credentials are confirmed,
 * so a wrong password still gets the usual error. Register it with
 * `Fortify::authenticateUsing(new AuthenticateActiveUser)`.
 */
class AuthenticateActiveUser
{
    public function __invoke(Request $request): ?Authenticatable
    {
        $username = (string) config('fortify.username', 'email');
        $provider = Auth::guard()->getProvider();

        $user = $provider->retrieveByCredentials([$username => $request->input($username)]);

        if ($user === null || ! $provider->validateCredentials($user, ['password' => $request->string('password')->toString()])) {
            return null;
        }

        if (method_exists($user, 'isDeactivated') && $user->isDeactivated()) {
            throw ValidationException::withMessages([
                $username => __('Your account has been deactivated.'),
            ]);
        }

        return $user;
    }
}
