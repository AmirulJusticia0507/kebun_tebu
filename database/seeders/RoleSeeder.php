<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view dashboard',
            'manage users',
            'manage categories',
            'manage blocks',
            'update report status',
            'create report',
            'view map',
            'export report',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        // Create Admin Role & assign all permissions
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions(Permission::all());

        // Create Field Officer Role & assign field permissions
        $officerRole = Role::findOrCreate('field_officer', 'web');
        $officerRole->syncPermissions([
            'create report',
            'view map',
            'export report',
        ]);
    }
}
