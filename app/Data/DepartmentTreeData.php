<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * Узел дерева оргструктуры, отображаемого на странице "Структура". Строится
 * в StructureController::buildTree, который добавляет к каждому узлу счётчики
 * сотрудников и открытых вакансий, не хранящиеся в модели.
 */
class DepartmentTreeData extends Data
{
    /**
     * @param  array<int, DepartmentTreeData>  $children
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $short_name,
        public ?string $code,
        public ?int $manager_id,
        public ?string $manager_name,
        public int $employees_count,
        public int $open_vacancies,
        #[DataCollectionOf(DepartmentTreeData::class)]
        public array $children,
    ) {}
}
