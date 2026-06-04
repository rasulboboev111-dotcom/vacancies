<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Policies\BranchPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $branchUser;

    private Branch $branch1;

    private Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $userRole = Role::firstOrCreate(['name' => 'User']);

        foreach (['view branches', 'create branches', 'edit branches', 'delete branches'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $adminRole->syncPermissions(Permission::all());
        $userRole->syncPermissions(['view branches']);

        $this->branch1 = Branch::create(['name' => 'Branch One', 'code' => 'B1']);
        $this->branch2 = Branch::create(['name' => 'Branch Two', 'code' => 'B2']);

        $this->admin = User::factory()->create(['branch_id' => null]);
        $this->admin->assignRole('Admin');

        $this->branchUser = User::factory()->create(['branch_id' => $this->branch1->id]);
        $this->branchUser->assignRole('User');
    }

    public function test_soft_deleted_branch_code_can_be_reused(): void
    {
        $old = Branch::create(['name' => 'Old', 'code' => 'DUP']);
        $old->delete(); // soft-delete frees the code

        $this->actingAs($this->admin)
            ->post(route('branches.store'), ['name' => 'Fresh', 'code' => 'DUP'])
            ->assertRedirect();

        $this->assertDatabaseHas('branches', ['name' => 'Fresh', 'code' => 'DUP', 'deleted_at' => null]);
    }

    public function test_active_branch_code_is_still_rejected(): void
    {
        Branch::create(['name' => 'Active', 'code' => 'ACT']);

        $this->actingAs($this->admin)
            ->from(route('structure.index'))
            ->post(route('branches.store'), ['name' => 'Clash', 'code' => 'ACT'])
            ->assertSessionHasErrors('code');
    }

    public function test_branch_policy_view_enforces_branch_equality(): void
    {
        $policy = new BranchPolicy;

        // Admin sees any branch.
        $this->assertTrue($policy->view($this->admin, $this->branch1));
        $this->assertTrue($policy->view($this->admin, $this->branch2));

        // Branch user sees only their own branch.
        $this->assertTrue($policy->view($this->branchUser, $this->branch1));
        $this->assertFalse($policy->view($this->branchUser, $this->branch2));
    }

    public function test_restore_blocked_when_code_reused_by_live_branch(): void
    {
        $old = Branch::create(['name' => 'Old', 'code' => 'REUSE']);
        $old->delete(); // to trash → frees the code (partial unique)
        Branch::create(['name' => 'New', 'code' => 'REUSE']);

        $this->actingAs($this->admin)
            ->from(route('trash.index'))
            ->post(route('trash.branches.restore', $old->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        // Restore refused, not a raw unique violation.
        $this->assertSoftDeleted('branches', ['id' => $old->id]);
    }
}
