<?php

test('rbac install command is registered by the package', function () {
    $this->artisan('help', ['command_name' => 'rbac:install'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Install the RBAC application integration');
});
