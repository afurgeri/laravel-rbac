<?php

namespace Modules\Rbac\Policies;

use Illuminate\Database\Eloquent\Model;
use Modules\Rbac\Contracts\HasPermissions;
use Modules\Rbac\RbacPermissions;

class RolePolicy
{
    public function viewAny(HasPermissions $user): bool
    {
        return $user->hasPermission(RbacPermissions::VIEW_ROLES);
    }

    public function create(HasPermissions $user): bool
    {
        return $user->hasPermission(RbacPermissions::CREATE_ROLES);
    }

    public function update(HasPermissions $user, Model $role): bool
    {
        return $role->getAttribute('name') !== config('rbac.protected_role', 'admin')
            && $user->hasPermission(RbacPermissions::UPDATE_ROLES);
    }

    public function delete(HasPermissions $user, Model $role): bool
    {
        return $role->getAttribute('name') !== config('rbac.protected_role', 'admin')
            && $user->hasPermission(RbacPermissions::DELETE_ROLES);
    }
}
