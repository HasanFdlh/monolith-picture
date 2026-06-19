<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // ADMIN
        $admin = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);

        // USER
        $user = Role::firstOrCreate([
            'name' => 'User',
            'guard_name' => 'web',
        ]);

        // Assign permissions ke ADMIN (full access)
        $admin->syncPermissions(Permission::all());

        // USER hanya dashboard
        $user->syncPermissions([
            'dashboard.view',
        ]);
    }
}
