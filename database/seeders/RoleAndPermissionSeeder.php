<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public const ROLE_SUPERADMIN = 'Superadmin';

    public const ROLE_ADMIN = 'Admin';

    public const ROLE_USER = 'User';

    /**
     * Email единственного суперадмина. Этому пользователю сидер выдаёт роль
     * «Superadmin» (только он чистит логи ротации/аудита, видит корзину и
     * редактирует/удаляет пользователей). Меняйте здесь или через .env.
     */
    private const SUPERADMIN_EMAIL = 'admin@hr.local';

    /** @var list<string> */
    private const ALLOWED_ROLES = [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_USER];

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
            'view applications',
            'create applications',
            'edit applications',
            'delete applications',
            'view audit logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Суперадмин — все права (плюс эксклюзивные разрушающие действия,
        // которые гейтятся отдельно через isSuperAdmin(), а не через permission).
        $superadminRole = Role::firstOrCreate(['name' => self::ROLE_SUPERADMIN]);
        $superadminRole->syncPermissions(Permission::all());

        // Админ — доступ ко всей информации
        $adminRole = Role::firstOrCreate(['name' => self::ROLE_ADMIN]);
        $adminRole->syncPermissions(Permission::all());

        // Пользователь: просмотр и изменения — только в своём филиале
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
            'view applications',
            'create applications',
            'edit applications',
            'delete applications',
        ]);

        $this->removeLegacyRoles();
        $this->normalizeUserRoles();

        // Назначаем единственного суперадмина по email (.env переопределяет).
        $superadminEmail = env('SUPERADMIN_EMAIL', self::SUPERADMIN_EMAIL);
        $superadmin = User::where('email', $superadminEmail)->first();
        if ($superadmin) {
            $superadmin->syncRoles([self::ROLE_SUPERADMIN]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Удалить все роли, кроме Superadmin, Admin и User (HR Manager, Branch
     * Manager, Viewer, Editor и др.).
     */
    private function removeLegacyRoles(): void
    {
        Role::whereNotIn('name', self::ALLOWED_ROLES)->delete();
    }

    /**
     * У каждого пользователя ровно одна роль: Superadmin, Admin или User.
     * Существующий Superadmin сохраняется (повторный сидинг его не сбрасывает).
     */
    private function normalizeUserRoles(): void
    {
        foreach (User::all() as $user) {
            if ($user->hasRole(self::ROLE_SUPERADMIN)) {
                $user->syncRoles([self::ROLE_SUPERADMIN]);

                continue;
            }

            if ($user->hasRole(self::ROLE_ADMIN)) {
                $user->syncRoles([self::ROLE_ADMIN]);

                continue;
            }

            $user->syncRoles([self::ROLE_USER]);
        }
    }
}
