<?php

namespace Modules\Rbac;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Rbac\Console\Commands\InstallRbacCommand;
use Modules\Rbac\Http\Middleware\EnsureUserHasPermission;

class RbacServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/rbac.php', 'rbac');
    }

    /**
     * Bootstrap any module services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallRbacCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');

        Route::aliasMiddleware('permission', EnsureUserHasPermission::class);
    }
}
