<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Rbac\Database\Seeders\AdminRoleSeeder;
use Modules\Rbac\Database\Seeders\AdminUserSeeder;
use Modules\Rbac\Database\Seeders\RbacPermissionSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RbacPermissionSeeder::class,
            AdminRoleSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
