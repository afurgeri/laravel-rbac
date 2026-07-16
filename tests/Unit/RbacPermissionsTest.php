<?php

use Modules\Rbac\RbacPermissions;

test('bundled permissions are unique and use resource action names', function () {
    $permissions = RbacPermissions::all();

    expect($permissions)->toHaveCount(count(array_unique($permissions)))
        ->and($permissions)->each->toMatch('/^[a-z]+\.[a-z]+$/');
});
