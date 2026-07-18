<?php

namespace Modules\Rbac\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Rbac\RbacModels;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property-read Collection<int, MongoRole> $roles
 */
#[Fillable(['name'])]
class MongoPermission extends Model
{
    protected $connection = 'mongodb';

    protected string $collection = 'permissions';

    protected $guarded = [];

    /**
     * @return Attribute<array<mixed>, never>
     */
    protected function roleIds(): Attribute
    {
        return Attribute::get(fn (mixed $value): array => is_array($value) ? $value : []);
    }

    /**
     * @return BelongsToMany<MongoRole, $this>
     */
    public function roles(): BelongsToMany
    {
        /** @var class-string<MongoRole> $roleModel */
        $roleModel = RbacModels::role();

        return $this->belongsToMany($roleModel, null, 'permission_ids', 'role_ids');
    }

    public function getTable(): string
    {
        return (string) config('rbac.tables.permissions', 'permissions');
    }
}
