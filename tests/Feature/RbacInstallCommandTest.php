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
        ->and($mysqlFiles)->toHaveKey('stubs/app/Http/Controllers/Rbac/RoleController.php')
        ->and($mongoFiles)
        ->toHaveKey('stubs/mongodb/config/rbac.php')
        ->toHaveKey('stubs/mongodb/app/Http/Controllers/Rbac/RoleController.php');
});
