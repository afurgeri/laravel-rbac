<?php

namespace Modules\Rbac\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Rbac\Models\Permission;
use Modules\Rbac\Models\Role;

class AdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $protectedRole = (string) config('rbac.protected_role', 'admin');

        $admin = Role::updateOrCreate(
            ['name' => $protectedRole],
            ['name' => $protectedRole],
        );

        $admin->permissions()->sync(Permission::query()->pluck('id')->all());
    }
}
