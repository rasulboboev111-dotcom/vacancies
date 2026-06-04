<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Rotation;
use App\Models\Position;
use App\Models\Structure;
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
            $buhgalter = Position::firstOrCreate(['name' => 'Бухгалтер']);
            $sarmuhosib = Position::firstOrCreate(['name' => 'Сармуҳосиб']);
            $otdelKadrov = Structure::firstOrCreate(['name' => 'Отдел кадров']);
            $shubaiMuhosibot = Structure::firstOrCreate(['name' => 'Шуъбаи муҳосибот']);

            Rotation::create([
                'employee_id' => $nur->id,
                'old_branch_id' => $kjd->id,
                'new_branch_id' => $dsh->id,
                'old_position_id' => $buhgalter->id,
                'new_position_id' => $sarmuhosib->id,
                'old_structure_id' => $otdelKadrov->id,
                'new_structure_id' => $shubaiMuhosibot->id,
                'rotation_date' => '2023-01-10',
                'reason' => 'Перевод в головной офис с повышением в должности за выдающиеся заслуги',
            ]);

            $starshiySpec = Position::firstOrCreate(['name' => 'Старший специалист']);
            $director = Position::firstOrCreate(['name' => 'Директор']);
            $apparatUpravleniya = Structure::firstOrCreate(['name' => 'Аппарат управления']);
            $departamentiMakhsus = Structure::firstOrCreate(['name' => 'Департаменти махсус']);

            Rotation::create([
                'employee_id' => $huseyn->id,
                'old_branch_id' => $dsh->id,
                'new_branch_id' => $kjd->id,
                'old_position_id' => $starshiySpec->id,
                'new_position_id' => $director->id,
                'old_structure_id' => $apparatUpravleniya->id,
                'new_structure_id' => $departamentiMakhsus->id,
                'rotation_date' => '2022-11-15',
                'reason' => 'Назначение на руководящую должность во вновь открывающийся филиал в г. Худжанд',
            ]);

            $directorFiliala = Position::firstOrCreate(['name' => 'Директор филиала']);
            $genDirector = Position::firstOrCreate(['name' => 'Генеральный директор']);
            $adminFiliala = Structure::firstOrCreate(['name' => 'Администрация филиала']);

            Rotation::create([
                'employee_id' => $behruz->id,
                'old_branch_id' => $bxt->id,
                'new_branch_id' => $dsh->id,
                'old_position_id' => $directorFiliala->id,
                'new_position_id' => $genDirector->id,
                'old_structure_id' => $adminFiliala->id,
                'new_structure_id' => $apparatUpravleniya->id,
                'rotation_date' => '2020-05-01',
                'reason' => 'Решение совета директоров о назначении на пост Генерального директора компании',
            ]);
        }
    }
}
