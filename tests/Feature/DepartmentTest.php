<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepartmentTest extends TestCase
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

        foreach ([
            'view branches',
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $adminRole->syncPermissions(Permission::all());
        $userRole->syncPermissions([
            'view branches',
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',
        ]);

        $this->branch1 = Branch::create(['name' => 'Branch One', 'code' => 'B1']);
        $this->branch2 = Branch::create(['name' => 'Branch Two', 'code' => 'B2']);

        $this->admin = User::factory()->create(['branch_id' => null]);
        $this->admin->assignRole('Admin');

        $this->branchUser = User::factory()->create(['branch_id' => $this->branch1->id]);
        $this->branchUser->assignRole('User');

        $this->userWithoutBranch = User::factory()->create(['branch_id' => null]);
        $this->userWithoutBranch->assignRole('User');
    }

    public function test_admin_can_create_root_department(): void
    {
        $response = $this->actingAs($this->admin)->post(route('departments.store'), [
            'branch_id' => $this->branch1->id,
            'parent_id' => null,
            'name' => 'IT Department',
            'code' => 'IT',
        ]);

        $response->assertRedirect(route('structure.index'));
        $this->assertDatabaseHas('departments', [
            'branch_id' => $this->branch1->id,
            'parent_id' => null,
            'name' => 'IT Department',
            'code' => 'IT',
        ]);
    }

    public function test_branch_user_can_create_department_in_own_branch(): void
    {
        $response = $this->actingAs($this->branchUser)->post(route('departments.store'), [
            'branch_id' => $this->branch1->id,
            'parent_id' => null,
            'name' => 'HR Department',
        ]);

        $response->assertRedirect(route('structure.index'));
        $this->assertDatabaseHas('departments', [
            'branch_id' => $this->branch1->id,
            'name' => 'HR Department',
        ]);
    }

    public function test_branch_user_cannot_create_department_in_other_branch(): void
    {
        $response = $this->actingAs($this->branchUser)->post(route('departments.store'), [
            'branch_id' => $this->branch2->id,
            'parent_id' => null,
            'name' => 'Foreign Department',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_without_branch_sees_empty_structure(): void
    {
        $response = $this->actingAs($this->userWithoutBranch)->get(route('structure.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Structure/Index')
            ->where('structure', [])
            ->where('branches', [])
            ->where('departmentsFlat', []));
    }

    public function test_duplicate_root_department_name_in_same_branch_is_rejected(): void
    {
        Department::create([
            'branch_id' => $this->branch1->id,
            'name' => 'Finance',
        ]);

        $response = $this->actingAs($this->admin)->post(route('departments.store'), [
            'branch_id' => $this->branch1->id,
            'parent_id' => null,
            'name' => 'Finance',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_parent_from_other_branch_is_rejected(): void
    {
        $foreignParent = Department::create([
            'branch_id' => $this->branch2->id,
            'name' => 'Foreign Parent',
        ]);

        $response = $this->actingAs($this->admin)->post(route('departments.store'), [
            'branch_id' => $this->branch1->id,
            'parent_id' => $foreignParent->id,
            'name' => 'Invalid Child',
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_update_with_cyclic_parent_is_rejected(): void
    {
        $parent = Department::create([
            'branch_id' => $this->branch1->id,
            'name' => 'Parent Department',
        ]);

        $child = Department::create([
            'branch_id' => $this->branch1->id,
            'parent_id' => $parent->id,
            'name' => 'Child Department',
        ]);

        $response = $this->actingAs($this->admin)->put(route('departments.update', $parent), [
            'branch_id' => $this->branch1->id,
            'parent_id' => $child->id,
            'name' => 'Parent Department',
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_department_with_children_cannot_be_deleted(): void
    {
        $parent = Department::create([
            'branch_id' => $this->branch1->id,
            'name' => 'Parent Department',
        ]);

        Department::create([
            'branch_id' => $this->branch1->id,
            'parent_id' => $parent->id,
            'name' => 'Child Department',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('departments.destroy', $parent));

        $response->assertRedirect(route('structure.index'));
        $response->assertSessionHasErrors('message');
        $this->assertDatabaseHas('departments', ['id' => $parent->id, 'deleted_at' => null]);
    }

    public function test_leaf_department_can_be_deleted(): void
    {
        $department = Department::create([
            'branch_id' => $this->branch1->id,
            'name' => 'Temporary Department',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('departments.destroy', $department));

        $response->assertRedirect(route('structure.index'));
        $this->assertSoftDeleted('departments', ['id' => $department->id]);
    }

    public function test_admin_can_create_sub_department(): void
    {
        $parent = Department::create([
            'branch_id' => $this->branch1->id,
            'name' => 'Parent Department',
            'code' => 'P1',
        ]);

        $response = $this->actingAs($this->admin)->post(route('departments.store'), [
            'branch_id' => $this->branch1->id,
            'parent_id' => $parent->id,
            'name' => 'Child Department',
            'code' => 'C1',
        ]);

        $response->assertRedirect(route('structure.index'));
        $this->assertDatabaseHas('departments', [
            'branch_id' => $this->branch1->id,
            'parent_id' => $parent->id,
            'name' => 'Child Department',
        ]);
    }

    public function test_department_tree_is_returned_in_structure(): void
    {
        $root = Department::create([
            'branch_id' => $this->branch1->id,
            'name' => 'Root Department',
        ]);

        Department::create([
            'branch_id' => $this->branch1->id,
            'parent_id' => $root->id,
            'name' => 'Nested Department',
        ]);

        $response = $this->actingAs($this->admin)->get(route('structure.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Structure/Index')
            ->has('structure.0.departments', 1)
            ->where('structure.0.departments.0.name', 'Root Department')
            ->has('structure.0.departments.0.children', 1)
            ->where('structure.0.departments.0.children.0.name', 'Nested Department')
            ->has('departmentsFlat', 2));
    }
}
