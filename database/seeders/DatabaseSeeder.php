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
                // Predictable password only in local/testing; a random one
                // elsewhere so a production seed never ships a known password.
                'password' => bcrypt(app()->environment('local', 'testing') ? 'password' : Str::random(32)),
            ]
        );

        // Branches, departments and employees come from the org structure
        // import (php artisan org:import), not from seeders. The default seed
        // only bootstraps the admin account and the roles/permissions.
        $this->call([
            RoleAndPermissionSeeder::class,
        ]);
    }
}
