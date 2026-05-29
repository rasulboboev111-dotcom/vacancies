<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public const ROLE_ADMIN = 'Admin';

    public const ROLE_USER = 'User';

    /** @var list<string> */
    private const ALLOWED_ROLES = [self::ROLE_ADMIN, self::ROLE_USER];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view branches',
            'create branches',
            'edit branches',
            'delete branches',
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',
            'view vacancies',
            'create vacancies',
            'edit vacancies',
            'delete vacancies',
            'view audit logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Админ — доступ ко всей информации
        $adminRole = Role::firstOrCreate(['name' => self::ROLE_ADMIN]);
        $adminRole->syncPermissions(Permission::all());

        // Пользователь: просмотр сотрудников всех филиалов; изменения — только в своём филиале
        $userRole = Role::firstOrCreate(['name' => self::ROLE_USER]);
        $userRole->syncPermissions([
            'view branches',
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',
            'view vacancies',
            'create vacancies',
            'edit vacancies',
            'delete vacancies',
        ]);

        $this->removeLegacyRoles();
        $this->normalizeUserRoles();

        $adminUser = User::where('email', 'admin@hr.local')->first();
        if ($adminUser) {
            $adminUser->syncRoles([self::ROLE_ADMIN]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Удалить все роли, кроме Admin и User (HR Manager, Branch Manager, Viewer, Editor и др.).
     */
    private function removeLegacyRoles(): void
    {
        Role::whereNotIn('name', self::ALLOWED_ROLES)->delete();
    }

    /**
     * У каждого пользователя ровно одна роль: Admin или User.
     */
    private function normalizeUserRoles(): void
    {
        foreach (User::all() as $user) {
            if ($user->hasRole(self::ROLE_ADMIN)) {
                $user->syncRoles([self::ROLE_ADMIN]);
                continue;
            }

            $user->syncRoles([self::ROLE_USER]);
        }
    }
}
