<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
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

        Permission::create(['name' => 'view branches']);
        Permission::create(['name' => 'view employees']);
        Permission::create(['name' => 'create employees']);
        Permission::create(['name' => 'edit employees']);
        Permission::create(['name' => 'delete employees']);

        $adminRole->syncPermissions(Permission::all());
        $userRole->syncPermissions([
            'view branches',
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
        ]);

        $this->branch1 = Branch::create(['name' => 'Branch One', 'code' => 'B1']);
        $this->branch2 = Branch::create(['name' => 'Branch Two', 'code' => 'B2']);

        $this->admin = User::factory()->create(['branch_id' => null]);
        $this->admin->assignRole('Admin');

        $this->branchUser = User::factory()->create(['branch_id' => $this->branch1->id]);
        $this->branchUser->assignRole('User');
    }

    public function test_non_admin_cannot_access_user_index(): void
    {
        $response = $this->actingAs($this->branchUser)->get(route('users.index'));
        $response->assertStatus(403);
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
            'role' => 'User',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'New Staff',
            'email' => 'staff@example.com',
            'branch_id' => $this->branch1->id,
        ]);

        $newUser = User::where('email', 'staff@example.com')->first();
        $this->assertTrue($newUser->hasRole('User'));
    }

    public function test_user_role_requires_branch(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('users.index'))
            ->post(route('users.store'), [
                'name' => 'No Branch',
                'email' => 'nobranch@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'branch_id' => null,
                'role' => 'User',
            ]);

        $response->assertSessionHasErrors('branch_id');
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
                'role' => 'User',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_update_user(): void
    {
        $targetUser = User::factory()->create(['branch_id' => $this->branch1->id]);
        $targetUser->assignRole('User');

        $response = $this->actingAs($this->admin)->put(route('users.update', $targetUser->id), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'branch_id' => $this->branch2->id,
            'role' => 'User',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'branch_id' => $this->branch2->id,
        ]);

        $this->assertTrue($targetUser->fresh()->hasRole('User'));
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

    public function test_branch_user_cannot_restore_employees_in_trash(): void
    {
        $deletedUser = User::factory()->create(['branch_id' => $this->branch1->id]);
        $deletedUser->delete();

        $response = $this->actingAs($this->branchUser)->get(route('trash.index'));
        $response->assertStatus(200);

        $this->assertCount(0, $response->viewData('page')['props']['users']);

        $responseRestore = $this->actingAs($this->branchUser)->post(route('trash.users.restore', $deletedUser->id));
        $responseRestore->assertStatus(403);

        $responseForce = $this->actingAs($this->branchUser)->delete(route('trash.users.force', $deletedUser->id));
        $responseForce->assertStatus(403);
    }

    public function test_user_without_branch_cannot_view_employees(): void
    {
        $userWithoutBranch = User::factory()->create(['branch_id' => null]);
        $userWithoutBranch->assignRole('User');

        $pos = Position::create(['name' => 'Manager']);
        Employee::create([
            'branch_id' => $this->branch1->id,
            'position_id' => $pos->id,
            'full_name' => 'John Doe',
            'gender' => 'male',
            'hire_date' => '2020-01-01',
        ]);

        $this->actingAs($userWithoutBranch)->get(route('employees.index'))->assertStatus(403);
        $this->actingAs($userWithoutBranch)->get(route('branches.index'))->assertStatus(403);
        $this->actingAs($userWithoutBranch)->get(route('positions.index'))->assertStatus(403);
    }

    public function test_branch_user_can_view_employees_from_other_branches(): void
    {
        $pos = Position::create(['name' => 'Manager']);
        Employee::create([
            'branch_id' => $this->branch2->id,
            'position_id' => $pos->id,
            'full_name' => 'Other Branch Employee',
            'gender' => 'Мужской',
            'hire_date' => '2020-01-01',
        ]);

        $response = $this->actingAs($this->branchUser)->get(route('employees.index'));
        $response->assertStatus(200);
        $response->assertSee('Other Branch Employee');
    }

    public function test_branch_user_can_create_employee_in_own_branch(): void
    {
        $pos = Position::create(['name' => 'Manager']);
        $category = \App\Models\Category::create(['name' => 'Office']);
        $structure = \App\Models\Structure::create(['name' => 'HQ']);

        $response = $this->actingAs($this->branchUser)->post(route('employees.store'), [
            'branch_id' => $this->branch1->id,
            'category_id' => $category->id,
            'type_id' => 'штатный',
            'full_name' => 'New Employee',
            'gender' => 'Мужской',
            'position_id' => $pos->id,
            'structure_id' => $structure->id,
            'hire_date' => '2020-01-01',
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'full_name' => 'New Employee',
            'branch_id' => $this->branch1->id,
        ]);
    }

    public function test_branch_user_cannot_create_employee_in_other_branch(): void
    {
        $pos = Position::create(['name' => 'Manager']);
        $category = \App\Models\Category::create(['name' => 'Office']);
        $structure = \App\Models\Structure::create(['name' => 'HQ']);

        $response = $this->actingAs($this->branchUser)->post(route('employees.store'), [
            'branch_id' => $this->branch2->id,
            'category_id' => $category->id,
            'type_id' => 'штатный',
            'full_name' => 'Wrong Branch',
            'gender' => 'Мужской',
            'position_id' => $pos->id,
            'structure_id' => $structure->id,
            'hire_date' => '2020-01-01',
        ]);

        $response->assertStatus(403);
    }
}
