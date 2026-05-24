<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
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
                'full_name' => 'Алиев Акмал Акмалович',
                'position' => 'Генеральный директор',
                'structure' => 'Аппарат управления',
                'direct_manager' => null,
                'hire_date' => '2020-01-15',
                'dismissal_date' => null,
                'passport_issued_by' => 'ОМВД Сино-1 г. Душанбе',
                'inn' => '123456789',
            ],
            [
                'branch_id' => $dsh->id,
                'category' => 'Руководство',
                'type' => 'Штатный',
                'full_name' => 'Каримов Баходур Баходурович',
                'position' => 'Заместитель директора',
                'structure' => 'Аппарат управления',
                'direct_manager' => 'Алиев Акмал Акмалович',
                'hire_date' => '2021-03-20',
                'dismissal_date' => null,
                'passport_issued_by' => 'ОМВД Шохмансур г. Душанбе',
                'inn' => '987654321',
            ],
            [
                'branch_id' => $dsh->id,
                'category' => 'Специалисты',
                'type' => 'Штатный',
                'full_name' => 'Саидова Нигина Хусейновна',
                'position' => 'Главный бухгалтер',
                'structure' => 'Бухгалтерия',
                'direct_manager' => 'Алиев Акмал Акмалович',
                'hire_date' => '2020-02-10',
                'dismissal_date' => null,
                'passport_issued_by' => 'ОМВД Фирдавси г. Душанбе',
                'inn' => '456123789',
            ],
            [
                'branch_id' => $dsh->id,
                'category' => 'Специалисты',
                'type' => 'Контракт',
                'full_name' => 'Одинаев Хуршед Сафарович',
                'position' => 'Системный администратор',
                'structure' => 'IT отдел',
                'direct_manager' => 'Каримов Баходур Баходурович',
                'hire_date' => '2022-06-01',
                'dismissal_date' => null,
                'passport_issued_by' => 'ОМВД г. Вахдат',
                'inn' => '789456123',
            ],
            [
                'branch_id' => $dsh->id,
                'category' => 'Обслуживающий персонал',
                'type' => 'Штатный',
                'full_name' => 'Рахимов Сомон Комронович',
                'position' => 'Водитель',
                'structure' => 'Хозяйственный отдел',
                'direct_manager' => 'Каримов Баходур Баходурович',
                'hire_date' => '2021-11-01',
                'dismissal_date' => null,
                'passport_issued_by' => 'ОМВД Сино-2 г. Душанбе',
                'inn' => '321654987',
            ],
            
            // Khujand
            [
                'branch_id' => $kjd->id,
                'category' => 'Руководство',
                'type' => 'Штатный',
                'full_name' => 'Мирзоев Джамшед Тохирович',
                'position' => 'Директор филиала',
                'structure' => 'Администрация филиала',
                'direct_manager' => 'Алиев Акмал Акмалович',
                'hire_date' => '2019-05-12',
                'dismissal_date' => null,
                'passport_issued_by' => 'ОМВД г. Худжанд',
                'inn' => '654789321',
            ],
            [
                'branch_id' => $kjd->id,
                'category' => 'Специалисты',
                'type' => 'Штатный',
                'full_name' => 'Бобоева Фарзона Рустамовна',
                'position' => 'Старший менеджер по продажам',
                'structure' => 'Отдел продаж',
                'direct_manager' => 'Мирзоев Джамшед Тохирович',
                'hire_date' => '2021-08-15',
                'dismissal_date' => null,
                'passport_issued_by' => 'ОМВД Б.Гафуровского района',
                'inn' => '159263487',
            ],
            [
                'branch_id' => $kjd->id,
                'category' => 'Специалисты',
                'type' => 'Внештатный',
                'full_name' => 'Хакимов Умедджон Салимович',
                'position' => 'Консультант',
                'structure' => 'Отдел поддержки',
                'direct_manager' => 'Мирзоев Джамшед Тохирович',
                'hire_date' => '2023-01-10',
                'dismissal_date' => '2024-03-01',
                'passport_issued_by' => 'ОМВД г. Истаравшан',
                'inn' => '357159846',
            ],

            // Bokhtar
            [
                'branch_id' => $bxt->id,
                'category' => 'Руководство',
                'type' => 'Штатный',
                'full_name' => 'Сафаров Мухаммад Алиевич',
                'position' => 'Директор филиала',
                'structure' => 'Администрация филиала',
                'direct_manager' => 'Алиев Акмал Акмалович',
                'hire_date' => '2021-06-01',
                'dismissal_date' => null,
                'passport_issued_by' => 'ОМВД г. Бохтар',
                'inn' => '258369147',
            ],
            [
                'branch_id' => $bxt->id,
                'category' => 'Специалисты',
                'type' => 'Штатный',
                'full_name' => 'Курбонов Фирдавс Назарович',
                'position' => 'Кредитный эксперт',
                'structure' => 'Кредитный отдел',
                'direct_manager' => 'Сафаров Мухаммад Алиевич',
                'hire_date' => '2022-02-15',
                'dismissal_date' => null,
                'passport_issued_by' => 'ОМВД г. Левакант',
                'inn' => '369147258',
            ],

            // Kulob
            [
                'branch_id' => $klb->id,
                'category' => 'Руководство',
                'type' => 'Штатный',
                'full_name' => 'Назаров Далер Шарифович',
                'position' => 'Директор филиала',
                'structure' => 'Администрация филиала',
                'direct_manager' => 'Алиев Акмал Акмалович',
                'hire_date' => '2020-09-01',
                'dismissal_date' => null,
                'passport_issued_by' => 'ОМВД г. Куляб',
                'inn' => '741852963',
            ],
            [
                'branch_id' => $klb->id,
                'category' => 'Специалисты',
                'type' => 'Штатный',
                'full_name' => 'Шарипова Мадина Саидовна',
                'position' => 'Кассир-операционист',
                'structure' => 'Операционный отдел',
                'direct_manager' => 'Назаров Далер Шарифович',
                'hire_date' => '2021-05-10',
                'dismissal_date' => null,
                'passport_issued_by' => 'ОМВД Восейского района',
                'inn' => '852963741',
            ],

            // Khorugh
            [
                'branch_id' => $khr->id,
                'category' => 'Руководство',
                'type' => 'Штатный',
                'full_name' => 'Мамадризоев Алишер Давлатбекович',
                'position' => 'Директор филиала',
                'structure' => 'Администрация филиала',
                'direct_manager' => 'Алиев Акмал Акмалович',
                'hire_date' => '2022-04-01',
                'dismissal_date' => null,
                'passport_issued_by' => 'ОМВД г. Хорог',
                'inn' => '963852741',
            ],
            [
                'branch_id' => $khr->id,
                'category' => 'Специалисты',
                'type' => 'Штатный',
                'full_name' => 'Давлатбекова Зарина Шодихоновна',
                'position' => 'Специалист по работе с клиентами',
                'structure' => 'Абонентский отдел',
                'direct_manager' => 'Мамадризоев Алишер Давлатбекович',
                'hire_date' => '2023-05-15',
                'dismissal_date' => null,
                'passport_issued_by' => 'ОМВД Шугнанского района',
                'inn' => '147258369',
            ],
        ];

        foreach ($employees as $employee) {
            Employee::create($employee);
        }
    }
}
