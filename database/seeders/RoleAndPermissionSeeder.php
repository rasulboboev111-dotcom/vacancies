<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view branches',
            'create branches',
            'edit branches',
            'delete branches',
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'view audit logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign existing permissions

        // Admin role (all permissions)
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->syncPermissions(Permission::all());

        // HR Manager role (can view and manage branches/employees, but maybe not delete branches or view audit logs, or let's give them most permissions)
        $hrManagerRole = Role::firstOrCreate(['name' => 'HR Manager']);
        $hrManagerRole->syncPermissions([
            'view branches',
            'create branches',
            'edit branches',
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
        ]);

        // Branch Manager role (we will implement branch-level scoping in policies, but they need general view/edit permissions)
        $branchManagerRole = Role::firstOrCreate(['name' => 'Branch Manager']);
        $branchManagerRole->syncPermissions([
            'view branches',
            'view employees',
            'create employees',
            'edit employees',
        ]);

        // Viewer role
        $viewerRole = Role::firstOrCreate(['name' => 'Viewer']);
        $viewerRole->syncPermissions([
            'view branches',
            'view employees',
        ]);

        // Assign Admin role to default admin user
        $adminUser = User::where('email', 'admin@hr.local')->first();
        if ($adminUser) {
            $adminUser->assignRole('Admin');
        }
    }
}
