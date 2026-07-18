<?php

namespace Modules\Rbac\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Crud\Concerns\HasCrudDefinition;
use Modules\Crud\Contracts\HasCrudDefinition as HasCrudDefinitionContract;
use Modules\Rbac\Crud\RoleCrudDefinition;
use Modules\Rbac\Policies\RolePolicy;
use Modules\Rbac\RbacModels;
use MongoDB\Laravel\Eloquent\Model as MongoModel;

/**
 * @property string $id
 * @property string $name
 * @property-read Collection<int, MongoPermission> $permissions
 */
#[Fillable(['name'])]
#[UsePolicy(RolePolicy::class)]
class MongoRole extends MongoModel implements HasCrudDefinitionContract
{
    use HasCrudDefinition;

    protected $connection = 'mongodb';

    protected string $collection = 'roles';

    protected $guarded = [];

    public static function crudDefinition(): string
    {
        return RoleCrudDefinition::class;
    }

    /**
     * @return Attribute<array<mixed>, never>
     */
    protected function permissionIds(): Attribute
    {
        return Attribute::get(fn (mixed $value): array => is_array($value) ? $value : []);
    }

    /**
     * @return Attribute<array<mixed>, never>
     */
    protected function userIds(): Attribute
    {
        return Attribute::get(fn (mixed $value): array => is_array($value) ? $value : []);
    }

    /**
     * @return BelongsToMany<MongoPermission, $this>
     */
    public function permissions(): BelongsToMany
    {
        /** @var class-string<MongoPermission> $permissionModel */
        $permissionModel = RbacModels::permission();

        return $this->belongsToMany($permissionModel, null, 'role_ids', 'permission_ids');
    }

    /**
     * @return BelongsToMany<Model, $this>
     */
    public function users(): BelongsToMany
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('rbac.models.user', 'App\\Models\\User');

        return $this->belongsToMany($userModel, null, 'role_ids', 'user_ids');
    }

    public function getTable(): string
    {
        return (string) config('rbac.tables.roles', 'roles');
    }
}
