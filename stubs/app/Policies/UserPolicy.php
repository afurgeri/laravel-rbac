<?php

namespace App\Policies;

use App\Models\User;
use Modules\Rbac\RbacPermissions;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(RbacPermissions::VIEW_USERS);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(RbacPermissions::CREATE_USERS);
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermission(RbacPermissions::UPDATE_USERS);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isNot($model)
            && $user->hasPermission(RbacPermissions::DELETE_USERS);
    }
}
