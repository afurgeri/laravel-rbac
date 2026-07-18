<?php

namespace Tests;

use Modules\Rbac\RbacServiceProvider;
use MongoDB\Laravel\MongoDBServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Tests\MongoDB\Fixtures\MongoRbacUser;

abstract class MongoDbTestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            RbacServiceProvider::class,
            MongoDBServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $app['config']->set('database.connections.mongodb', [
            'driver' => 'mongodb',
            'dsn' => getenv('MONGODB_URI') ?: 'mongodb://127.0.0.1:27017',
            'database' => getenv('MONGODB_DATABASE') ?: 'rbac_integration_test',
        ]);
        $app['config']->set('rbac.storage', 'mongodb');
        $app['config']->set('rbac.models.user', MongoRbacUser::class);
    }
}
