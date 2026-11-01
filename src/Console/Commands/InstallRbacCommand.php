<?php

namespace Modules\Rbac\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Rbac\Console\ApplicationPatcher;

class InstallRbacCommand extends Command
{
    protected $signature = 'rbac:install {--database=mysql : The database connector to target (mysql or mongodb)} {--force : Overwrite existing RBAC integration files}';

    protected $description = 'Install the RBAC application integration';

    /**
     * @var array<string, string>
     */
    private const FILES = [
        'stubs/app/Policies/UserPolicy.php' => 'app/Policies/UserPolicy.php',
        'stubs/routes-rbac.php' => 'routes/rbac.php',
        'stubs/resources/js/pages/roles/Index.vue' => 'resources/js/pages/roles/Index.vue',
        'stubs/resources/js/pages/roles/Create.vue' => 'resources/js/pages/roles/Create.vue',
        'stubs/resources/js/pages/roles/Edit.vue' => 'resources/js/pages/roles/Edit.vue',
        'stubs/resources/js/pages/roles/Show.vue' => 'resources/js/pages/roles/Show.vue',
        'stubs/resources/js/pages/roles/RolePermissionsField.vue' => 'resources/js/pages/roles/RolePermissionsField.vue',
        'stubs/resources/js/pages/roles/RolePermissionsSummary.vue' => 'resources/js/pages/roles/RolePermissionsSummary.vue',
        'stubs/resources/js/types/roles.ts' => 'resources/js/types/roles.ts',
        'stubs/resources/js/pages/users/Index.vue' => 'resources/js/pages/users/Index.vue',
        'stubs/resources/js/pages/users/Create.vue' => 'resources/js/pages/users/Create.vue',
        'stubs/resources/js/pages/users/Edit.vue' => 'resources/js/pages/users/Edit.vue',
        'stubs/resources/js/pages/users/Show.vue' => 'resources/js/pages/users/Show.vue',
        'stubs/resources/js/pages/users/UserPasswordDialog.vue' => 'resources/js/pages/users/UserPasswordDialog.vue',
        'stubs/resources/js/pages/users/UserRolesField.vue' => 'resources/js/pages/users/UserRolesField.vue',
    ];

    public function handle(): int
    {
        $database = $this->option('database');

        if (! in_array($database, ['mysql', 'mongodb'], true)) {
            $this->components->error('The --database option must be either mysql or mongodb.');

            return self::FAILURE;
        }

        if ($database === 'mongodb' && ! class_exists('MongoDB\\Laravel\\Eloquent\\Model')) {
            $this->components->error('MongoDB support is not installed. Run [composer require mongodb/laravel-mongodb] first.');

            return self::FAILURE;
        }

        if (! File::exists(base_path('resources/js/components/crud/CrudPage.vue'))) {
            $this->components->error('Generic CRUD frontend files are missing. Run [php artisan crud:install] first.');

            return self::FAILURE;
        }

        $crudTypes = base_path('resources/js/types/crud.ts');
        $crudForm = base_path('resources/js/components/crud/CrudForm.vue');

        if (
            ! File::exists($crudTypes)
            || ! str_contains(File::get($crudTypes), 'page_width: CrudLayoutWidth;')
            || ! File::exists($crudForm)
            || ! str_contains(File::get($crudForm), 'field-${field.name}')
        ) {
            $this->components->error('Generic CRUD frontend files are outdated. Run [php artisan crud:install --upgrade] first.');

            return self::FAILURE;
        }

        $packageRoot = dirname(__DIR__, 3);
        $targets = [];

        foreach ($this->filesFor($database) as $source => $target) {
            $targets[] = [
                'source' => $packageRoot.'/'.$source,
                'target' => base_path($target),
            ];
        }

        $conflicts = array_values(array_filter(
            $targets,
            fn (array $file): bool => File::exists($file['target']),
        ));

        if ($conflicts !== [] && ! $this->option('force')) {
            $this->components->error('RBAC integration files already exist. Use --force to overwrite them:');

            foreach ($conflicts as $file) {
                $this->line('  - '.$file['target']);
            }

            return self::FAILURE;
        }

        $patcher = new ApplicationPatcher;

        foreach ($targets as $file) {
            File::ensureDirectoryExists(dirname($file['target']));
            File::copy($file['source'], $file['target']);

            if ($database === 'mysql' && str_ends_with($file['target'], '.vue')) {
                File::put($file['target'], $patcher->numericRouteKeys(File::get($file['target'])));
            }
        }

        $this->appendRouteRequire();

        $this->components->info('RBAC application integration installed.');

        $manual = $this->patchApplicationFiles($patcher, $database);

        $this->newLine();

        foreach ($manual as $instruction) {
            $this->components->warn($instruction);
        }

        $this->line($database === 'mongodb'
            ? 'Then configure the MongoDB connection and run: php artisan db:seed'
            : 'Then run: php artisan migrate --seed');

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function filesFor(string $database): array
    {
        $files = [
            "stubs/{$database}/config/rbac.php" => 'config/rbac.php',
        ];

        $files += $database === 'mongodb'
            ? [
                'stubs/mongodb/app/Crud/UserCrudDefinition.php' => 'app/Crud/UserCrudDefinition.php',
                'stubs/mongodb/app/Http/Controllers/Rbac/RoleController.php' => 'app/Http/Controllers/Rbac/RoleController.php',
                'stubs/mongodb/app/Http/Controllers/Rbac/UserController.php' => 'app/Http/Controllers/Rbac/UserController.php',
            ]
            : [
                'stubs/app/Crud/UserCrudDefinition.php' => 'app/Crud/UserCrudDefinition.php',
                'stubs/app/Http/Controllers/Rbac/RoleController.php' => 'app/Http/Controllers/Rbac/RoleController.php',
                'stubs/app/Http/Controllers/Rbac/UserController.php' => 'app/Http/Controllers/Rbac/UserController.php',
            ];

        return [...$files, ...self::FILES];
    }

    /**
     * Applies the starter kit integration and returns what still has to be done by hand.
     *
     * @return list<string>
     */
    private function patchApplicationFiles(ApplicationPatcher $patcher, string $database): array
    {
        $patches = [
            'app/Models/User.php' => [
                fn (string $contents): array => $patcher->userModel($contents),
                'Update App\\Models\\User to use HasRoles, CanBeDeactivated and HasCrudDefinition, and implement HasPermissions.',
            ],
            'database/seeders/DatabaseSeeder.php' => [
                fn (string $contents): array => $patcher->databaseSeeder($contents),
                'Call RbacPermissionSeeder, AdminRoleSeeder and AdminUserSeeder from DatabaseSeeder.',
            ],
            'bootstrap/app.php' => [
                fn (string $contents): array => $patcher->bootstrapApp($contents),
                'Append Modules\\Rbac\\Http\\Middleware\\EnsureUserIsActive to the web middleware group.',
            ],
            'app/Providers/FortifyServiceProvider.php' => [
                fn (string $contents): array => $patcher->fortifyServiceProvider($contents),
                'Reject deactivated users at login, e.g. Fortify::authenticateUsing(new AuthenticateActiveUser).',
            ],
            'app/Http/Middleware/HandleInertiaRequests.php' => [
                fn (string $contents): array => $patcher->inertiaMiddleware($contents),
                "Share the user's permissions as auth.permissions from HandleInertiaRequests.",
            ],
            'resources/js/types/auth.ts' => [
                fn (string $contents): array => $patcher->authTypes($contents),
                'Add permissions: string[] to the Auth type.',
            ],
        ];

        if ($database === 'mongodb') {
            unset($patches['app/Models/User.php']);
        }

        $manual = $database === 'mongodb'
            ? ['Update App\\Models\\User to extend the MongoDB authenticatable model, use HasRoles, CanBeDeactivated and HasCrudDefinition, and implement HasPermissions.']
            : [];

        foreach ($patches as $relativePath => [$patch, $instruction]) {
            $path = base_path($relativePath);

            if (! File::exists($path)) {
                $manual[] = $instruction;

                continue;
            }

            $result = $patch(File::get($path));

            if ($result['status'] === ApplicationPatcher::PATCHED) {
                File::put($path, $result['contents']);
                $this->components->twoColumnDetail($relativePath, '<fg=green>UPDATED</>');
            } elseif ($result['status'] === ApplicationPatcher::ALREADY_PATCHED) {
                $this->components->twoColumnDetail($relativePath, '<fg=gray>ALREADY INTEGRATED</>');
            } else {
                $this->components->twoColumnDetail($relativePath, '<fg=yellow>MANUAL</>');
                $manual[] = $instruction;
            }
        }

        return $manual;
    }

    private function appendRouteRequire(): void
    {
        $path = base_path('routes/web.php');

        if (! File::exists($path)) {
            $this->components->warn("routes/web.php was not found; add require __DIR__.'/rbac.php'; manually.");

            return;
        }

        $contents = File::get($path);
        $require = "require __DIR__.'/rbac.php';";

        if (str_contains($contents, $require)) {
            return;
        }

        File::put($path, rtrim($contents)."\n\n{$require}\n");
    }
}
