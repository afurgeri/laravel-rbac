<?php

namespace Tests\MongoDB\Fixtures;

use Modules\Rbac\Concerns\HasRoles;
use Modules\Rbac\Contracts\HasPermissions;
use MongoDB\Laravel\Eloquent\Model;

class MongoRbacUser extends Model implements HasPermissions
{
    use HasRoles;

    protected $connection = 'mongodb';

    protected $collection = 'rbac_users';

    protected $guarded = [];
}
