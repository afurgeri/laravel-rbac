<?php

namespace Modules\Rbac\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Rbac\Models\Role;

trait HasRoles
{
    /**
     * @var array<string, bool>
     */
    private array $permissionCache = [];

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            (string) config('rbac.tables.role_user', 'role_user'),
            'user_id',
            'role_id',
        );
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissionCache[$permission] ??= $this->roles()
            ->whereHas('permissions', function (Builder $query) use ($permission): void {
                $query->where('name', $permission);
            })
            ->exists();
    }
}
