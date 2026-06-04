<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $branchUser;

    private User $userWithoutBranch;

    private Branch $branch1;

    private Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $userRole = Role::firstOrCreate(['name' => 'User']);

        foreach (['view employees', 'view audit logs'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $adminRole->syncPermissions(Permission::all());
        // Even when a branch user is granted the audit permission, the listing
        // must stay scoped to their own branch.
        $userRole->syncPermissions(['view employees', 'view audit logs']);

        $this->branch1 = Branch::create(['name' => 'Branch One', 'code' => 'B1']);
        $this->branch2 = Branch::create(['name' => 'Branch Two', 'code' => 'B2']);

        $this->admin = User::factory()->create(['branch_id' => null]);
        $this->admin->assignRole('Admin');

        $this->branchUser = User::factory()->create(['branch_id' => $this->branch1->id]);
        $this->branchUser->assignRole('User');

        $this->userWithoutBranch = User::factory()->create(['branch_id' => null]);
        $this->userWithoutBranch->assignRole('User');

        $position = Position::create(['name' => 'Engineer']);

        // Each create() auto-logs an activity whose subject is the employee, so
        // the logged properties carry the (searchable) full_name.
        Employee::create([
            'branch_id' => $this->branch1->id,
            'full_name' => 'OwnBranchPerson',
            'gender' => 'мужской',
            'position_id' => $position->id,
            'hire_date' => '2020-01-01',
        ]);
        Employee::create([
            'branch_id' => $this->branch2->id,
            'full_name' => 'OtherBranchPerson',
            'gender' => 'мужской',
            'position_id' => $position->id,
            'hire_date' => '2020-01-01',
        ]);
    }

    public function test_admin_sees_all_branches_logs(): void
    {
        $response = $this->actingAs($this->admin)->get(route('activity-logs.index'));

        $response->assertOk();
        $response->assertSee('OwnBranchPerson', false);
        $response->assertSee('OtherBranchPerson', false);
    }

    public function test_branch_user_sees_only_own_branch_logs(): void
    {
        $response = $this->actingAs($this->branchUser)->get(route('activity-logs.index'));

        $response->assertOk();
        $response->assertSee('OwnBranchPerson', false);
        $response->assertDontSee('OtherBranchPerson', false);
    }

    public function test_branchless_user_sees_no_logs(): void
    {
        $response = $this->actingAs($this->userWithoutBranch)->get(route('activity-logs.index'));

        $response->assertOk();
        $response->assertDontSee('OwnBranchPerson', false);
        $response->assertDontSee('OtherBranchPerson', false);
    }
}
