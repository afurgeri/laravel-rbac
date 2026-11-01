<?php

namespace Modules\Rbac\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Rbac\RbacModels;

trait HasRoles
{
    /**
     * @var array<string, bool>
     */
    private array $permissionCache = [];

    /**
     * @return Attribute<array<mixed>, never>
     */
    protected function roleIds(): Attribute
    {
        return Attribute::get(fn (mixed $value): array => is_array($value) ? $value : []);
    }

    /**
     * @return BelongsToMany<Model, $this>
     */
    public function roles(): BelongsToMany
    {
        $roleModel = RbacModels::role();

        if (config('rbac.storage', 'mysql') === 'mongodb') {
            return $this->belongsToMany($roleModel, null, 'user_ids', 'role_ids');
        }

        return $this->belongsToMany(
            $roleModel,
            (string) config('rbac.tables.role_user', 'role_user'),
            'user_id',
            'role_id',
        );
    }

    /**
     * Names of every permission granted through the user's roles.
     *
     * @return list<string>
     */
    public function permissionNames(): array
    {
        return array_values($this->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn (Model $role): array => method_exists($role, 'permissions')
                ? $role->getRelation('permissions')->all()
                : [])
            ->map(fn (Model $permission): string => (string) $permission->getAttribute('name'))
            ->unique()
            ->values()
            ->all());
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
