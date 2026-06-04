<?php

namespace Tests\Feature;

use App\Enums\OrgStatus;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportOrgStructureTest extends TestCase
{
    use RefreshDatabase;

    private function import(): int
    {
        return $this->artisan('org:import', [
            '--file' => 'tests/Fixtures/org_structure.json',
            '--fresh' => true,
        ])->run();
    }

    public function test_import_creates_one_branch_per_business_unit(): void
    {
        $this->import();

        // The company (51) plus the Sughd filial unit (595).
        $this->assertEqualsCanonicalizing(
            [51, 595],
            Branch::orderBy('external_id')->pluck('external_id')->all(),
        );
    }

    public function test_import_builds_the_department_tree_with_metadata(): void
    {
        $this->import();

        $this->assertSame(4, Department::count());

        $hq = Department::where('external_id', 89)->first();
        $this->assertSame('HQ', $hq->short_name);
        $this->assertSame(OrgStatus::ACTIVE, $hq->status);

        // A sub-unit keeps its head office as parent within the same branch.
        $sub = Department::where('external_id', 100)->first();
        $this->assertSame($hq->id, $sub->parent_id);

        // The filial header (mis-tagged to the parent company in the source) is
        // promoted to its filial unit and roots that branch's tree.
        $filial = Department::where('external_id', 200)->first();
        $this->assertNull($filial->parent_id);
        $this->assertEquals(595, Branch::find($filial->branch_id)->external_id);

        // Its children stay attached beneath it — one connected tree per filial.
        $sughdTech = Department::where('external_id', 210)->first();
        $this->assertSame($filial->id, $sughdTech->parent_id);
        $this->assertEquals(595, Branch::find($sughdTech->branch_id)->external_id);
    }

    public function test_filial_header_and_its_staff_move_to_the_filial_branch(): void
    {
        $this->import();

        $sughd = Branch::where('external_id', 595)->first();
        $filial = Department::where('external_id', 200)->first();

        // The header department belongs to the filial branch, not the company.
        $this->assertEquals($sughd->id, $filial->branch_id);

        // The filial's director follows the header into the filial branch and
        // stays consistent with the department's branch (no orphaned staff).
        $director = Employee::where('external_id', 12)->first();
        $this->assertEquals($sughd->id, $director->branch_id);
        $this->assertSame($filial->id, $director->department_id);
    }

    public function test_import_links_department_manager_and_employee_fields(): void
    {
        $this->import();

        $boss = Employee::where('external_id', 10)->first();
        $this->assertSame(1000, $boss->person_id);
        $this->assertSame(OrgStatus::ACTIVE, $boss->status);

        // "Is a manager" is now derived from the authoritative department link.
        $hq = Department::where('external_id', 89)->first();
        $this->assertSame($boss->id, $hq->manager_id);
    }

    public function test_import_resolves_positions_by_job_title_id(): void
    {
        $this->import();

        $this->assertSame(5, Position::where('name', 'Director')->value('external_id'));
        $this->assertSame(3, Position::count());
    }

    public function test_import_is_idempotent(): void
    {
        $this->import();
        $this->import();

        $this->assertSame(2, Branch::count());
        $this->assertSame(4, Department::count());
        $this->assertSame(3, Employee::count());
    }
}
