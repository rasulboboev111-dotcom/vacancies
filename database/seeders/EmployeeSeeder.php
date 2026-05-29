<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Category;
use App\Models\Position;
use App\Models\Structure;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dsh = Branch::where('code', 'DSH')->first();
        $kjd = Branch::where('code', 'KJD')->first();
        $bxt = Branch::where('code', 'BXT')->first();
        $klb = Branch::where('code', 'KLB')->first();
        $khr = Branch::where('code', 'KHR')->first();

        $employees = [
            // Dushanbe
            [
                'branch_id' => $dsh->id,
                'category' => 'Руководство',
                'type' => 'Штатный',
                'full_name' => 'Абдухафторзода Бехруз А.',
                'gender' => 'Мужской',
                'position' => 'Генеральный директор',
                'structure' => 'Аппарат управления',
                'direct_manager' => null,
                'hire_date' => '2015-04-03',
                'dismissal_date' => null,
                'birth_date' => '1985-09-20',
                'nationality' => 'Тоҷик',
                'passport_number' => 'A05977277',
                'passport_start_date' => '2023-09-20',
                'passport_end_date' => '2033-08-19',
                'passport_issued_by' => 'ШВКД дар н. И. Сомонӣ',
                'inn' => '023166498',
                'sin' => 'V2130310931223',
                'address' => 'ш. Душанбе, к. Шероз, дом 72, кв 15',
                'phone_number' => '988080878',
                'birth_place' => 'ш. Душанбе',
                'education' => 'Олӣ (магистр)',
                'specialty' => 'Муҳандиси техникӣ',
                'employment_start_date' => '2009-01-15',
            ],
            [
                'branch_id' => $dsh->id,
                'category' => 'Руководство',
                'type' => 'Штатный',
                'full_name' => 'Каримов Нур Мирзоевич',
                'gender' => 'Мужской',
                'position' => 'Сармуҳосиб',
                'structure' => 'Шуъбаи муҳосибот',
                'direct_manager' => 'Абдухафторзода Бехруз А.',
                'hire_date' => '2019-06-24',
                'dismissal_date' => null,
                'birth_date' => '1982-03-10',
                'nationality' => 'Тоҷик',
                'passport_number' => 'A04878189',
                'passport_start_date' => '2016-01-26',
                'passport_end_date' => '2026-01-25',
                'passport_issued_by' => 'ШВКД-2 дар н. Фирдавсӣ',
                'inn' => '015316498',
                'sin' => 'V21203108441216',
                'address' => 'ш. Душанбе, к. Фирдавсӣ, к. Навоӣ, д 4А',
                'phone_number' => '555555121',
                'birth_place' => 'в. Суғд',
                'education' => 'Олӣ (бакалавр)',
                'specialty' => 'Иқтисоддон',
                'employment_start_date' => '2001-07-24',
            ],
            [
                'branch_id' => $dsh->id,
                'category' => 'Специалисты',
                'type' => 'Штатный',
                'full_name' => 'Зухуров Ризо Бахорович',
                'gender' => 'Мужской',
                'position' => 'Сардори Раёсат',
                'structure' => 'Департаменти техникӣ',
                'direct_manager' => 'Абдухафторзода Бехруз А.',
                'hire_date' => '2020-02-10',
                'dismissal_date' => null,
                'birth_date' => '1990-11-10',
                'nationality' => 'Тоҷик',
                'passport_number' => 'A7525773',
                'passport_start_date' => '2012-02-05',
                'passport_end_date' => '2022-02-04',
                'passport_issued_by' => 'ШВКД дар ш. Кӯлоб',
                'inn' => '195121808',
                'sin' => 'V21303108422312',
                'address' => 'ш. Душанбе, к. Сомонӣ, д 110',
                'phone_number' => '907721110',
                'birth_place' => 'в. Хатлон',
                'education' => 'Олӣ (магистр)',
                'specialty' => 'Барномасоз',
                'employment_start_date' => '2008-04-10',
            ],
            [
                'branch_id' => $dsh->id,
                'category' => 'Специалисты',
                'type' => 'Контракт',
                'full_name' => 'Шарифов Курбонали Рахмоналиевич',
                'gender' => 'Мужской',
                'position' => 'Сардори Шуъба',
                'structure' => 'Департаменти амният',
                'direct_manager' => 'Абдухафторзода Бехруз А.',
                'hire_date' => '2021-06-01',
                'dismissal_date' => '2026-02-20',
                'birth_date' => '1987-04-01',
                'nationality' => 'Тоҷик',
                'passport_number' => 'A04096485',
                'passport_start_date' => '2021-12-18',
                'passport_end_date' => '2031-12-17',
                'passport_issued_by' => 'ШВКД дар н. Ваҳдат',
                'inn' => '055511871',
                'sin' => 'V1810419872407',
                'address' => 'ш. Ваҳдат, к. Р. Ризо, д 1-15',
                'phone_number' => '919171987',
                'birth_place' => 'ш. Ваҳдат',
                'education' => 'Олӣ (бакалавр)',
                'specialty' => 'Ҳуқуқшинос',
                'employment_start_date' => '2003-07-01',
            ],
            
            // Khujand
            [
                'branch_id' => $kjd->id,
                'category' => 'Руководство',
                'type' => 'Штатный',
                'full_name' => 'Зарипов Хусейн Ибрагимович',
                'gender' => 'Мужской',
                'position' => 'Директор',
                'structure' => 'Департаменти махсус',
                'direct_manager' => 'Абдухафторзода Бехруз А.',
                'hire_date' => '2019-02-03',
                'dismissal_date' => null,
                'birth_date' => '1982-01-01',
                'nationality' => 'Тоҷик',
                'passport_number' => 'A02841451',
                'passport_start_date' => '2019-12-20',
                'passport_end_date' => '2029-12-19',
                'passport_issued_by' => 'ШВКД-2 дар н. Фирдавсӣ',
                'inn' => '017079498',
                'sin' => 'V20101198257725',
                'address' => 'н. Фирдавсӣ, д 4, к. Карабоев, д 5',
                'phone_number' => '907709878',
                'birth_place' => 'в. Суғд',
                'education' => 'Олӣ (магистр)',
                'specialty' => 'Муҳандиси техникӣ',
                'employment_start_date' => '2000-08-10',
            ],
            [
                'branch_id' => $kjd->id,
                'category' => 'Специалисты',
                'type' => 'Штатный',
                'full_name' => 'Бобоева Фарзона Рустамовна',
                'gender' => 'Женский',
                'position' => 'Старший менеджер по продажам',
                'structure' => 'Отдел продаж',
                'direct_manager' => 'Зарипов Хусейн Ибрагимович',
                'hire_date' => '2021-08-15',
                'dismissal_date' => null,
                'birth_date' => '1993-05-12',
                'nationality' => 'Тоҷик',
                'passport_number' => 'A1592634',
                'passport_start_date' => '2018-04-10',
                'passport_end_date' => '2028-04-09',
                'passport_issued_by' => 'ОМВД Б.Гафуровского района',
                'inn' => '159263487',
                'sin' => 'V1930512932822',
                'address' => 'н. Б.Гафуров, к. Ленина, д 12',
                'phone_number' => '927159263',
                'birth_place' => 'н. Б.Гафуров',
                'education' => 'Олӣ (бакалавр)',
                'specialty' => 'Иқтисоддон',
                'employment_start_date' => '2016-11-15',
            ],

            // Bokhtar
            [
                'branch_id' => $bxt->id,
                'category' => 'Руководство',
                'type' => 'Штатный',
                'full_name' => 'Сафаров Мухаммад Алиевич',
                'gender' => 'Мужской',
                'position' => 'Директор филиала',
                'structure' => 'Администрация филиала',
                'direct_manager' => 'Абдухафторзода Бехруз А.',
                'hire_date' => '2021-06-01',
                'dismissal_date' => null,
                'birth_date' => '1988-12-15',
                'nationality' => 'Тоҷик',
                'passport_number' => 'A2583691',
                'passport_start_date' => '2020-05-10',
                'passport_end_date' => '2030-05-09',
                'passport_issued_by' => 'ОМВД г. Бохтар',
                'inn' => '258369147',
                'sin' => 'V1881215934123',
                'address' => 'г. Бохтар, к. Вахдат, д 44',
                'phone_number' => '900258369',
                'birth_place' => 'г. Бохтар',
                'education' => 'Олӣ (магистр)',
                'specialty' => 'Менеджер',
                'employment_start_date' => '2012-09-20',
            ],

            // Kulob
            [
                'branch_id' => $klb->id,
                'category' => 'Руководство',
                'type' => 'Штатный',
                'full_name' => 'Назаров Далер Шарифович',
                'gender' => 'Мужской',
                'position' => 'Директор филиала',
                'structure' => 'Администрация филиала',
                'direct_manager' => 'Абдухафторзода Бехруз А.',
                'hire_date' => '2020-09-01',
                'dismissal_date' => null,
                'birth_date' => '1984-07-22',
                'nationality' => 'Тоҷик',
                'passport_number' => 'A7418529',
                'passport_start_date' => '2015-08-15',
                'passport_end_date' => '2025-08-14',
                'passport_issued_by' => 'ОМВД г. Куляб',
                'inn' => '741852963',
                'sin' => 'V1840722934121',
                'address' => 'г. Куляб, к. Сомони, д 10',
                'phone_number' => '918741852',
                'birth_place' => 'г. Куляб',
                'education' => 'Олӣ (бакалавр)',
                'specialty' => 'Муҳандис',
                'employment_start_date' => '2005-03-01',
            ],

            // Khorugh
            [
                'branch_id' => $khr->id,
                'category' => 'Руководство',
                'type' => 'Штатный',
                'full_name' => 'Мамадризоев Алишер Давлатбекович',
                'gender' => 'Мужской',
                'position' => 'Директор филиала',
                'structure' => 'Администрация филиала',
                'direct_manager' => 'Абдухафторзода Бехруз А.',
                'hire_date' => '2022-04-01',
                'dismissal_date' => null,
                'birth_date' => '1989-03-29',
                'nationality' => 'Тоҷик',
                'passport_number' => 'A9638527',
                'passport_start_date' => '2019-11-20',
                'passport_end_date' => '2029-11-19',
                'passport_issued_by' => 'ОМВД г. Хорог',
                'inn' => '963852741',
                'sin' => 'V1890329934123',
                'address' => 'г. Хорог, к. Шириншо Шотемур, д 55',
                'phone_number' => '935963852',
                'birth_place' => 'ВМКБ',
                'education' => 'Олӣ (бакалавр)',
                'specialty' => 'Иқтисоддон',
                'employment_start_date' => '2015-04-15',
            ],
        ];

        // Create unique lookup records
        $categoriesList = [];
        $positionsList = [];
        $structuresList = [];
 
        foreach ($employees as $emp) {
            if (!empty($emp['category'])) $categoriesList[] = $emp['category'];
            if (!empty($emp['position'])) $positionsList[] = $emp['position'];
            if (!empty($emp['structure'])) $structuresList[] = $emp['structure'];
        }
 
        $categoriesMap = [];
        foreach (array_unique($categoriesList) as $name) {
            $cat = Category::firstOrCreate(['name' => $name]);
            $categoriesMap[$name] = $cat->id;
        }
 
        $positionsMap = [];
        foreach (array_unique($positionsList) as $name) {
            $p = Position::firstOrCreate(['name' => $name]);
            $positionsMap[$name] = $p->id;
        }
 
        $structuresMap = [];
        foreach (array_unique($structuresList) as $name) {
            $s = Structure::firstOrCreate(['name' => $name]);
            $structuresMap[$name] = $s->id;
        }

        // Insert employees
        $createdEmployees = [];
        $employeeManagerMapping = [];

        foreach ($employees as $emp) {
            $employeeData = $emp;

            $employeeData['category_id'] = $categoriesMap[$emp['category']] ?? null;
            $employeeData['employment_type'] = mb_strtolower($emp['type']);
            $employeeData['gender'] = mb_strtolower($emp['gender']);
            $employeeData['position_id'] = $positionsMap[$emp['position']] ?? null;
            $employeeData['structure_id'] = $structuresMap[$emp['structure']] ?? null;

            unset($employeeData['category']);
            unset($employeeData['type']);
            unset($employeeData['position']);
            unset($employeeData['structure']);
            unset($employeeData['direct_manager']);

            $newEmployee = Employee::create($employeeData);
            $createdEmployees[$newEmployee->full_name] = $newEmployee;

            if (!empty($emp['direct_manager'])) {
                $employeeManagerMapping[$newEmployee->id] = $emp['direct_manager'];
            }
        }

        // Resolve managers
        foreach ($employeeManagerMapping as $employeeId => $managerName) {
            if (isset($createdEmployees[$managerName])) {
                Employee::where('id', $employeeId)->update([
                    'manager_id' => $createdEmployees[$managerName]->id
                ]);
            }
        }
    }
}
