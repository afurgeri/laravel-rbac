<?php

namespace Modules\Rbac\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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

        foreach ($targets as $file) {
            File::ensureDirectoryExists(dirname($file['target']));
            File::copy($file['source'], $file['target']);
        }

        $this->appendRouteRequire();

        $this->components->info('RBAC application integration installed.');
        $this->newLine();
        $this->components->warn('Update App\\Models\\User to use HasRoles and implement HasPermissions.');
        $this->line($database === 'mongodb'
            ? 'Then configure the MongoDB connection and run your application seeders.'
            : 'Then run: php artisan migrate');
        $this->line('Then register your application seeders in DatabaseSeeder.');

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
