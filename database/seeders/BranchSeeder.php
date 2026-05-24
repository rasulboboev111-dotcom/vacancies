<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Душанбе (Головной офис)',
                'code' => 'DSH',
                'address' => 'г. Душанбе, пр. Рудаки 100',
            ],
            [
                'name' => 'Худжандский филиал',
                'code' => 'KJD',
                'address' => 'г. Худжанд, ул. Ленина 45',
            ],
            [
                'name' => 'Бохтарский филиал',
                'code' => 'BXT',
                'address' => 'г. Бохтар, ул. Вахш 12',
            ],
            [
                'name' => 'Кулябский филиал',
                'code' => 'KLB',
                'address' => 'г. Куляб, ул. И. Сомони 88',
            ],
            [
                'name' => 'Хорогский филиал',
                'code' => 'KHR',
                'address' => 'г. Хорог, ул. Шириншо Шотемур 3',
            ],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(['code' => $branch['code']], $branch);
        }
    }
}
