<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
                'password' => bcrypt('password'),
            ]
        );

        $this->call([
            BranchSeeder::class,
            EmployeeSeeder::class,
            RoleAndPermissionSeeder::class,
        ]);
    }
}
