<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@hr.local'],
            [
                'name' => 'HR Administrator',
                // Предсказуемый пароль только в local/testing; в остальных
                // случаях случайный, чтобы продакшен-сид никогда не поставлял
                // известный пароль.
                'password' => bcrypt(app()->environment('local', 'testing') ? 'password' : Str::random(32)),
            ]
        );

        // Филиалы, подразделения и сотрудники приходят из импорта оргструктуры
        // (php artisan org:import), а не из сидеров. Стандартный сид лишь
        // бутстрапит учётную запись администратора и роли/разрешения.
        $this->call([
            RoleAndPermissionSeeder::class,
        ]);
    }
}
