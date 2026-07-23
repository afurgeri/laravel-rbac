<?php

use Modules\Rbac\Console\Commands\InstallRbacCommand;

test('rbac install command is registered by the package', function () {
    $this->artisan('help', ['command_name' => 'rbac:install'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Install the RBAC application integration')
        ->expectsOutputToContain('--database');
});

test('rbac install rejects an unknown database connector', function () {
    $this->artisan('rbac:install', ['--database' => 'pgsql'])
        ->assertExitCode(1)
        ->expectsOutputToContain('The --database option must be either mysql or mongodb.');
});

test('rbac install selects connector-specific integration stubs', function () {
    $command = app(InstallRbacCommand::class);
    $filesFor = fn (string $database): array => $this->filesFor($database);

    $mysqlFiles = $filesFor->call($command, 'mysql');
    $mongoFiles = $filesFor->call($command, 'mongodb');

    expect($mysqlFiles)
        ->toHaveKey('stubs/mysql/config/rbac.php')
        ->toHaveKey('stubs/app/Crud/UserCrudDefinition.php')
        ->toHaveKey('stubs/app/Http/Controllers/Rbac/RoleController.php')
        ->toHaveKey('stubs/app/Http/Controllers/Rbac/UserController.php')
        ->and($mongoFiles)
        ->toHaveKey('stubs/mongodb/config/rbac.php')
        ->toHaveKey('stubs/mongodb/app/Crud/UserCrudDefinition.php')
        ->toHaveKey('stubs/mongodb/app/Http/Controllers/Rbac/RoleController.php')
        ->toHaveKey('stubs/mongodb/app/Http/Controllers/Rbac/UserController.php');

    foreach (['roles', 'users'] as $resource) {
        foreach (['Index', 'Create', 'Edit', 'Show'] as $page) {
            expect($mysqlFiles)
                ->toHaveKey("stubs/resources/js/pages/{$resource}/{$page}.vue")
                ->and($mongoFiles)
                ->toHaveKey("stubs/resources/js/pages/{$resource}/{$page}.vue");
        }
    }
});

test('rbac integration stubs expose page CRUD and cross-database pagination types', function () {
    $moduleRoot = dirname(__DIR__, 2);
    $controllerFiles = [
        $moduleRoot.'/stubs/app/Http/Controllers/Rbac/RoleController.php',
        $moduleRoot.'/stubs/app/Http/Controllers/Rbac/UserController.php',
        $moduleRoot.'/stubs/mongodb/app/Http/Controllers/Rbac/RoleController.php',
        $moduleRoot.'/stubs/mongodb/app/Http/Controllers/Rbac/UserController.php',
    ];

    foreach ($controllerFiles as $file) {
        expect(file_get_contents($file))->toContain('CrudOperationGuard');
    }

    expect(file_get_contents($moduleRoot.'/stubs/app/Http/Controllers/Rbac/RoleController.php'))
        ->toContain("Inertia::render('roles/Show'");

    expect(file_get_contents($moduleRoot.'/stubs/app/Http/Controllers/Rbac/UserController.php'))
        ->toContain("Inertia::render('users/Show'");

    expect(file_get_contents($moduleRoot.'/stubs/mongodb/app/Http/Controllers/Rbac/RoleController.php'))
        ->toContain("Inertia::render('roles/Show'");

    expect(file_get_contents($moduleRoot.'/stubs/mongodb/app/Http/Controllers/Rbac/UserController.php'))
        ->toContain("Inertia::render('users/Show'");

    expect(file_get_contents($moduleRoot.'/stubs/app/Crud/UserCrudDefinition.php'))
        ->toContain('HasCrudFormMode')
        ->toContain('HasCrudOperations')
        ->toContain('return CrudFormMode::Page;')
        ->toContain('CrudOperation::Show');

    expect(file_get_contents($moduleRoot.'/stubs/mongodb/app/Crud/UserCrudDefinition.php'))
        ->toContain('HasCrudFormMode')
        ->toContain('HasCrudOperations')
        ->toContain('return CrudFormMode::Page;')
        ->toContain('CrudOperation::Show');

    expect(file_get_contents($moduleRoot.'/stubs/app/Policies/UserPolicy.php'))
        ->toContain('public function view(User $user, User $model): bool');

    expect(file_get_contents($moduleRoot.'/stubs/resources/js/pages/roles/Index.vue'))
        ->toContain('CrudPaginator')
        ->toContain('show as showRole')
        ->toContain('record.can.show');

    expect(file_get_contents($moduleRoot.'/stubs/resources/js/pages/users/Index.vue'))
        ->toContain('CrudPaginator')
        ->toContain('show as showUser')
        ->toContain('record.can.show');

    expect(file_get_contents($moduleRoot.'/stubs/routes-rbac.php'))
        ->toContain("['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']")
        ->toContain("Route::patch('users/{user}/password'");
});
