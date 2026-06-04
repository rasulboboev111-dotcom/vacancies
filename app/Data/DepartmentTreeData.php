<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A node in the org-structure tree rendered on the Structure page. Built by
 * StructureController::buildTree, which injects the per-node employee and
 * open-vacancy counts that are not stored on the model.
 */
#[TypeScript]
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
