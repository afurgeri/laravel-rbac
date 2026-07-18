<?php

namespace Modules\Rbac\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Rbac\RbacModels;
use Modules\Rbac\RbacPermissions;

class RbacPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionModel = RbacModels::permission();

        foreach (RbacPermissions::all() as $permission) {
            $attributes = ['name' => $permission];

            if (config('rbac.storage', 'mysql') === 'mongodb') {
                $attributes['role_ids'] = [];
            }

            $permissionModel::updateOrCreate(['name' => $permission], $attributes);
        }
    }
}
