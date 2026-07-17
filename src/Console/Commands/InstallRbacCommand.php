<?php

namespace Modules\Rbac\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallRbacCommand extends Command
{
    protected $signature = 'rbac:install {--force : Overwrite existing RBAC integration files}';

    protected $description = 'Install the RBAC application integration';

    /**
     * @var array<string, string>
     */
    private const FILES = [
        'config/rbac.php' => 'config/rbac.php',
        'stubs/app/Crud/UserCrudDefinition.php' => 'app/Crud/UserCrudDefinition.php',
        'stubs/app/Http/Controllers/Rbac/RoleController.php' => 'app/Http/Controllers/Rbac/RoleController.php',
        'stubs/app/Http/Controllers/Rbac/UserController.php' => 'app/Http/Controllers/Rbac/UserController.php',
        'stubs/app/Policies/UserPolicy.php' => 'app/Policies/UserPolicy.php',
        'stubs/routes-rbac.php' => 'routes/rbac.php',
        'stubs/resources/js/pages/roles/Index.vue' => 'resources/js/pages/roles/Index.vue',
        'stubs/resources/js/pages/roles/RolePermissionsField.vue' => 'resources/js/pages/roles/RolePermissionsField.vue',
        'stubs/resources/js/pages/users/Index.vue' => 'resources/js/pages/users/Index.vue',
        'stubs/resources/js/pages/users/UserPasswordDialog.vue' => 'resources/js/pages/users/UserPasswordDialog.vue',
        'stubs/resources/js/pages/users/UserRolesField.vue' => 'resources/js/pages/users/UserRolesField.vue',
    ];

    public function handle(): int
    {
        if (! File::exists(base_path('resources/js/components/crud/CrudPage.vue'))) {
            $this->components->error('Generic CRUD frontend files are missing. Run [php artisan crud:install] first.');

            return self::FAILURE;
        }

        $packageRoot = dirname(__DIR__, 3);
        $targets = [];

        foreach (self::FILES as $source => $target) {
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
        $this->line('Then run: php artisan migrate');
        $this->line('Then register your application seeders in DatabaseSeeder.');

        return self::SUCCESS;
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
