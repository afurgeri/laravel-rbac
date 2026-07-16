<?php

namespace Modules\Rbac\Contracts;

interface HasPermissions
{
    public function hasPermission(string $permission): bool;
}
