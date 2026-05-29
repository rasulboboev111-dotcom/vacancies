<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** @var list<string> */
    private const DEPARTMENT_PERMISSIONS = [
        'view departments',
        'create departments',
        'edit departments',
        'delete departments',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::DEPARTMENT_PERMISSIONS as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $adminRole = Role::query()->where('name', 'Admin')->where('guard_name', 'web')->first();
        $userRole = Role::query()->where('name', 'User')->where('guard_name', 'web')->first();

        if ($adminRole) {
            $adminRole->givePermissionTo(self::DEPARTMENT_PERMISSIONS);
        }

        if ($userRole) {
            $userRole->givePermissionTo(self::DEPARTMENT_PERMISSIONS);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $adminRole = Role::query()->where('name', 'Admin')->where('guard_name', 'web')->first();
        $userRole = Role::query()->where('name', 'User')->where('guard_name', 'web')->first();

        foreach (self::DEPARTMENT_PERMISSIONS as $permission) {
            $adminRole?->revokePermissionTo($permission);
            $userRole?->revokePermissionTo($permission);
            Permission::query()
                ->where('name', $permission)
                ->where('guard_name', 'web')
                ->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
