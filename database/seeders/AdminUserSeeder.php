<?php

namespace Modules\Rbac\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\Rbac\RbacModels;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the initial administrator and give it the protected role.
     */
    public function run(): void
    {
        $userModel = RbacModels::user();
        $email = (string) config('rbac.admin_user.email', 'admin@example.com');

        $admin = $userModel::query()->firstOrCreate(['email' => $email], [
            'name' => (string) config('rbac.admin_user.name', 'Admin'),
            'password' => (string) config('rbac.admin_user.password', 'password'),
        ]);

        $role = RbacModels::role()::query()
            ->where('name', (string) config('rbac.protected_role', 'admin'))
            ->first();

        if (! $role instanceof Model) {
            throw new RuntimeException('Run AdminRoleSeeder before AdminUserSeeder so the protected role exists.');
        }

        if (! method_exists($admin, 'roles')) {
            throw new RuntimeException('The configured RBAC user model must use the HasRoles trait.');
        }

        $admin->roles()->sync([$role->getKey()], false);
    }
}
