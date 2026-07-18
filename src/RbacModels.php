<?php

namespace Modules\Rbac;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Rbac\Models\MongoPermission;
use Modules\Rbac\Models\MongoRole;
use Modules\Rbac\Models\Permission;
use Modules\Rbac\Models\Role;

final class RbacModels
{
    /**
     * @return class-string<Model>
     */
    public static function role(): string
    {
        return self::resolve('role', config('rbac.storage', 'mysql') === 'mongodb' ? MongoRole::class : Role::class);
    }

    /**
     * @return class-string<Model>
     */
    public static function permission(): string
    {
        return self::resolve('permission', config('rbac.storage', 'mysql') === 'mongodb' ? MongoPermission::class : Permission::class);
    }

    /**
     * @return class-string<Model>
     */
    private static function resolve(string $key, string $default): string
    {
        $model = config("rbac.models.{$key}") ?: $default;

        if (! is_string($model) || ! class_exists($model) || ! is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException("The configured RBAC {$key} model must be an Eloquent model class.");
        }

        /** @var class-string<Model> $model */
        return $model;
    }
}
