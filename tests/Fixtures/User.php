<?php

namespace Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Rbac\Concerns\CanBeDeactivated;
use Modules\Rbac\Concerns\HasRoles;
use Modules\Rbac\Contracts\HasPermissions;

class User extends Authenticatable implements HasPermissions
{
    use CanBeDeactivated, HasRoles;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
