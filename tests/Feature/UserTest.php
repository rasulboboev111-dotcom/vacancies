<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;
    private User $branchManager;
    private Branch $branch1;
    private Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'Admin']);
        $viewerRole = Role::create(['name' => 'Viewer']);
        $branchManagerRole = Role::create(['name' => 'Branch Manager']);
        $hrManagerRole = Role::create(['name' => 'HR Manager']);

        // Create permissions
        $viewBranches = \Spatie\Permission\Models\Permission::create(['name' => 'view branches']);
        $viewEmployees = \Spatie\Permission\Models\Permission::create(['name' => 'view employees']);

        // Sync permissions
        $adminRole->syncPermissions([$viewBranches, $viewEmployees]);
        $viewerRole->syncPermissions([$viewBranches, $viewEmployees]);
        $branchManagerRole->syncPermissions([$viewBranches, $viewEmployees]);

        // Create branches
        $this->branch1 = Branch::create(['name' => 'Branch One', 'code' => 'B1']);
        $this->branch2 = Branch::create(['name' => 'Branch Two', 'code' => 'B2']);

        // Create users
        $this->admin = User::factory()->create(['branch_id' => null]);
        $this->admin->assignRole('Admin');

        $this->viewer = User::factory()->create(['branch_id' => $this->branch1->id]);
        $this->viewer->assignRole('Viewer');

        $this->branchManager = User::factory()->create(['branch_id' => $this->branch1->id]);
        $this->branchManager->assignRole('Branch Manager');
    }

    public function test_non_admin_cannot_access_user_index(): void
    {
        $response = $this->actingAs($this->viewer)->get(route('users.index'));
        $response->assertStatus(403);

        $response2 = $this->actingAs($this->branchManager)->get(route('users.index'));
        $response2->assertStatus(403);
    }

    public function test_admin_can_access_user_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('users.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_user_with_role(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'New Staff',
            'email' => '  STAFF@example.com  ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'branch_id' => $this->branch1->id,
            'role' => 'Viewer',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'New Staff',
            'email' => 'staff@example.com',
            'branch_id' => $this->branch1->id,
        ]);

        $newUser = User::where('email', 'staff@example.com')->first();
        $this->assertTrue($newUser->hasRole('Viewer'));
    }

    public function test_user_email_uniqueness_is_case_insensitive_and_trimmed(): void
    {
        User::factory()->create([
            'email' => 'staff@example.com'
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('users.index'))
            ->post(route('users.store'), [
                'name' => 'Duplicate Email Staff',
                'email' => '  STAFF@EXAMPLE.COM  ',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'branch_id' => $this->branch1->id,
                'role' => 'Viewer',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_update_user(): void
    {
        $targetUser = User::factory()->create(['branch_id' => $this->branch1->id]);
        $targetUser->assignRole('Viewer');

        $response = $this->actingAs($this->admin)->put(route('users.update', $targetUser->id), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'branch_id' => $this->branch2->id,
            'role' => 'Branch Manager',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'branch_id' => $this->branch2->id,
        ]);

        $this->assertTrue($targetUser->fresh()->hasRole('Branch Manager'));
        $this->assertFalse($targetUser->fresh()->hasRole('Viewer'));
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('users.destroy', $this->admin->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_admin_can_delete_other_user(): void
    {
        $targetUser = User::factory()->create();
        
        $response = $this->actingAs($this->admin)->delete(route('users.destroy', $targetUser->id));
        $response->assertRedirect(route('users.index'));
        $this->assertSoftDeleted('users', ['id' => $targetUser->id]);
    }

    public function test_non_admin_cannot_see_users_in_trash_or_manage_them(): void
    {
        $deletedUser = User::factory()->create(['branch_id' => $this->branch1->id]);
        $deletedUser->delete();

        // 1. Index should not return users for non-admin
        $response = $this->actingAs($this->branchManager)->get(route('trash.index'));
        $response->assertStatus(200);
        
        $this->assertCount(0, $response->viewData('page')['props']['users']);

        // 2. Restore should abort 403
        $responseRestore = $this->actingAs($this->branchManager)->post(route('trash.users.restore', $deletedUser->id));
        $responseRestore->assertStatus(403);

        // 3. Force delete should abort 403
        $responseForce = $this->actingAs($this->branchManager)->delete(route('trash.users.force', $deletedUser->id));
        $responseForce->assertStatus(403);
    }

    public function test_non_admin_with_null_branch_id_sees_no_branches_or_positions_or_employees(): void
    {
        $nullBranchUser = User::factory()->create(['branch_id' => null]);
        $nullBranchUser->assignRole('Viewer');

        // Create an employee in branch 1
        $pos = Position::create(['name' => 'Manager']);
        Employee::create([
            'branch_id' => $this->branch1->id,
            'position_id' => $pos->id,
            'full_name' => 'John Doe',
            'gender' => 'male',
            'hire_date' => '2020-01-01',
        ]);

        // 1. Employees Index - should return 403 Forbidden
        $response = $this->actingAs($nullBranchUser)->get(route('employees.index'));
        $response->assertStatus(403);

        // 2. Branches Index - should return 403 Forbidden
        $responseBranches = $this->actingAs($nullBranchUser)->get(route('branches.index'));
        $responseBranches->assertStatus(403);

        // 3. Positions Index - should return 403 Forbidden
        $responsePositions = $this->actingAs($nullBranchUser)->get(route('positions.index'));
        $responsePositions->assertStatus(403);
    }
}
