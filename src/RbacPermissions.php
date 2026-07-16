<?php

namespace Modules\Rbac;

final class RbacPermissions
{
    public const VIEW_ROLES = 'roles.view';

    public const CREATE_ROLES = 'roles.create';

    public const UPDATE_ROLES = 'roles.update';

    public const DELETE_ROLES = 'roles.delete';

    public const VIEW_USERS = 'users.view';

    public const CREATE_USERS = 'users.create';

    public const UPDATE_USERS = 'users.update';

    public const DELETE_USERS = 'users.delete';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW_ROLES,
            self::CREATE_ROLES,
            self::UPDATE_ROLES,
            self::DELETE_ROLES,
            self::VIEW_USERS,
            self::CREATE_USERS,
            self::UPDATE_USERS,
            self::DELETE_USERS,
        ];
    }
}
