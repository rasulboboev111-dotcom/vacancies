<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_popup_flags_members_with_the_management_category(): void
    {
        Role::firstOrCreate(['name' => 'Admin']);
        $admin = User::factory()->create(['branch_id' => null]);
        $admin->assignRole('Admin');

        $branch = Branch::create(['name' => 'Branch', 'code' => 'B1']);
        $parent = Department::create(['branch_id' => $branch->id, 'name' => 'Parent']);

        // A plain member and a member whose category marks them as a manager.
        $plain = Employee::create([
            'branch_id' => $branch->id,
            'department_id' => $parent->id,
            'full_name' => 'Plain Member',
            'category' => 'Мутахассис',
        ]);
        $manager = Employee::create([
            'branch_id' => $branch->id,
            'department_id' => $parent->id,
            'full_name' => 'Manager Member',
            'category' => 'Роҳбарият',
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('structure.department.employees', $parent));

        $response->assertOk();

        $employees = collect($response->json('employees'))->keyBy('id');
        $this->assertTrue($employees[$manager->id]['is_manager']);
        $this->assertFalse($employees[$plain->id]['is_manager']);
    }

    public function test_branch_user_sees_employees_of_their_own_department(): void
    {
        Role::firstOrCreate(['name' => 'User']);

        $branch = Branch::create(['name' => 'Own Branch', 'code' => 'OWN']);
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'Dept']);
        Employee::create([
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'full_name' => 'Member',
        ]);

        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('User');

        // Re-fetch so branch_id reflects the stored DB type — mirroring how
        // $request->user() is resolved at runtime. Without the integer cast the
        // strict branch comparison would 403 even on the user's own branch.
        $user = User::findOrFail($user->id);

        $response = $this->actingAs($user)
            ->getJson(route('structure.department.employees', $department));

        $response->assertOk();
        $this->assertCount(1, $response->json('employees'));
    }
}
