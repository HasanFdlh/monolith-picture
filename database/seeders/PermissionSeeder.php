<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // dashboard
            'dashboard.view',

            // users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // roles
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
