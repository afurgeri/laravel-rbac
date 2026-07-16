<?php

namespace Modules\Rbac\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Rbac\Models\Permission;
use Modules\Rbac\RbacPermissions;

class RbacPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (RbacPermissions::all() as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission],
                ['name' => $permission],
            );
        }
    }
}
