<?php

use Modules\Rbac\Models\Permission;
use Modules\Rbac\PermissionCatalog;

beforeEach(fn () => migrateWithUsers());

test('it groups permissions by subject in the usual action order with readable labels', function () {
    config([
        'rbac.permission_subjects' => ['roles' => 'Roles', 'users' => 'Users', 'leads' => 'Leads'],
        'rbac.permission_actions' => ['export' => 'Export', 'leads.convert' => 'Convert to member'],
    ]);

    foreach (['users.export', 'users.delete', 'roles.view', 'leads.convert', 'users.view', 'legacy.thing'] as $name) {
        Permission::query()->create(['name' => $name]);
    }

    $catalog = app(PermissionCatalog::class)->all();

    expect(array_column($catalog, 'label'))->toBe([
        'Leads: convert to member',
        'legacy.thing',
        'Roles: view',
        'Users: view',
        'Users: delete',
        'Users: export',
    ])
        ->and($catalog[0])->toMatchArray(['subject' => 'Leads', 'column' => null, 'action' => 'Convert to member'])
        ->and($catalog[4])->toMatchArray(['subject' => 'Users', 'column' => 'delete', 'action' => 'Delete'])
        ->and($catalog[1])->toMatchArray(['subject' => 'legacy.thing', 'column' => null, 'action' => 'legacy.thing']);
});

test('it translates the labels to the application locale', function () {
    app()->setLocale('es');
    Permission::query()->create(['name' => 'users.export']);

    expect(app(PermissionCatalog::class)->all()[0])
        ->toMatchArray(['label' => 'Usuarios: exportar', 'subject' => 'Usuarios', 'action' => 'Exportar']);
});
