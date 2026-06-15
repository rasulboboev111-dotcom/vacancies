<?php

namespace App\Data;

use App\Models\Department;
use Spatie\LaravelData\Data;

/**
 * Плоская запись отдела для выбора отдела на странице "Структура"
 * (departmentsFlat). Ключи повторяют то, что уже читают Vue-компоненты.
 */
class DepartmentListItemData extends Data
{
    public function __construct(
        public int $id,
        public ?int $branch_id,
        public ?int $parent_id,
        public string $name,
        public ?string $short_name,
        public ?string $code,
        public ?int $sort_order,
        public int $children_count,
    ) {}

    public static function fromModel(Department $department): self
    {
        return new self(
            id: $department->id,
            branch_id: $department->branch_id,
            parent_id: $department->parent_id,
            name: $department->name,
            short_name: $department->short_name,
            code: $department->code,
            sort_order: $department->sort_order,
            children_count: $department->children_count,
        );
    }
}
