<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Rotation;
use Illuminate\Database\Seeder;

class RotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dsh = Branch::where('code', 'DSH')->first();
        $kjd = Branch::where('code', 'KJD')->first();
        $bxt = Branch::where('code', 'BXT')->first();

        // Let's find some employees
        $behruz = Employee::where('full_name', 'Абдухафторзода Бехруз А.')->first();
        $nur = Employee::where('full_name', 'Каримов Нур Мирзоевич')->first();
        $huseyn = Employee::where('full_name', 'Зарипов Хусейн Ибрагимович')->first();

        if ($behruz && $nur && $huseyn) {
            Rotation::create([
                'employee_id' => $nur->id,
                'old_branch_id' => $kjd->id,
                'new_branch_id' => $dsh->id,
                'old_position' => 'Бухгалтер',
                'new_position' => 'Сармуҳосиб',
                'old_structure' => 'Отдел кадров',
                'new_structure' => 'Шуъбаи муҳосибот',
                'rotation_date' => '2023-01-10',
                'reason' => 'Перевод в головной офис с повышением в должности за выдающиеся заслуги',
            ]);

            Rotation::create([
                'employee_id' => $huseyn->id,
                'old_branch_id' => $dsh->id,
                'new_branch_id' => $kjd->id,
                'old_position' => 'Старший специалист',
                'new_position' => 'Директор',
                'old_structure' => 'Аппарат управления',
                'new_structure' => 'Департаменти махсус',
                'rotation_date' => '2022-11-15',
                'reason' => 'Назначение на руководящую должность во вновь открывающийся филиал в г. Худжанд',
            ]);

            Rotation::create([
                'employee_id' => $behruz->id,
                'old_branch_id' => $bxt->id,
                'new_branch_id' => $dsh->id,
                'old_position' => 'Директор филиала',
                'new_position' => 'Генеральный директор',
                'old_structure' => 'Администрация филиала',
                'new_structure' => 'Аппарат управления',
                'rotation_date' => '2020-05-01',
                'reason' => 'Решение совета директоров о назначении на пост Генерального директора компании',
            ]);
        }
    }
}
