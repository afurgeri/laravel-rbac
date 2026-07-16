<?php

namespace Modules\Rbac\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 */
#[Fillable(['name'])]
class Permission extends Model
{
    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            (string) config('rbac.tables.permission_role', 'permission_role'),
            'permission_id',
            'role_id',
        );
    }

    public function getTable(): string
    {
        return (string) config('rbac.tables.permissions', parent::getTable());
    }
}
