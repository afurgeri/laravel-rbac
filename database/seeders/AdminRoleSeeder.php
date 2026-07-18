<?php

namespace Modules\Rbac\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Rbac\RbacModels;
use RuntimeException;

class AdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $protectedRole = (string) config('rbac.protected_role', 'admin');

        $roleModel = RbacModels::role();
        $permissionModel = RbacModels::permission();

        $attributes = ['name' => $protectedRole];

        if (config('rbac.storage', 'mysql') === 'mongodb') {
            $attributes['permission_ids'] = [];
        }

        $admin = $roleModel::updateOrCreate(['name' => $protectedRole], $attributes);

        if (! method_exists($admin, 'permissions')) {
            throw new RuntimeException('The configured RBAC role model must define a permissions relationship.');
        }

        $admin->permissions()->sync($permissionModel::query()->pluck('id')->all());
    }
}
