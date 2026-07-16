<?php

namespace Modules\Rbac\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Crud\Concerns\HasCrudDefinition;
use Modules\Crud\Contracts\HasCrudDefinition as HasCrudDefinitionContract;
use Modules\Rbac\Crud\RoleCrudDefinition;
use Modules\Rbac\Policies\RolePolicy;

/**
 * @property int $id
 * @property string $name
 * @property-read Collection<int, Permission> $permissions
 */
#[Fillable(['name'])]
#[UsePolicy(RolePolicy::class)]
class Role extends Model implements HasCrudDefinitionContract
{
    use HasCrudDefinition;

    public static function crudDefinition(): string
    {
        return RoleCrudDefinition::class;
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            (string) config('rbac.tables.permission_role', 'permission_role'),
            'role_id',
            'permission_id',
        );
    }

    /**
     * @return BelongsToMany<Model, $this>
     */
    public function users(): BelongsToMany
    {
        $userModel = $this->userModel();

        return $this->belongsToMany(
            $userModel,
            (string) config('rbac.tables.role_user', 'role_user'),
            'role_id',
            'user_id',
        );
    }

    public function getTable(): string
    {
        return (string) config('rbac.tables.roles', parent::getTable());
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        $userModel = config('rbac.models.user')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        if (! is_string($userModel) || ! class_exists($userModel) || ! is_subclass_of($userModel, Model::class)) {
            throw new \InvalidArgumentException('The configured RBAC user model must be an Eloquent model class.');
        }

        /** @var class-string<Model> $userModel */
        return $userModel;
    }
}
