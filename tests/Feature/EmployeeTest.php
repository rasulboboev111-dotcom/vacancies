<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Nationality;
use App\Models\Position;
use App\Models\Rotation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $branchUser;

    private Branch $branch1;

    private Branch $branch2;

    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $userRole = Role::firstOrCreate(['name' => 'User']);

        foreach (['view employees', 'create employees', 'edit employees', 'delete employees'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $adminRole->syncPermissions(Permission::all());
        $userRole->syncPermissions(['view employees', 'create employees', 'edit employees', 'delete employees']);

        $this->branch1 = Branch::create(['name' => 'Branch One', 'code' => 'B1']);
        $this->branch2 = Branch::create(['name' => 'Branch Two', 'code' => 'B2']);

        $this->admin = User::factory()->create(['branch_id' => null]);
        $this->admin->assignRole('Admin');

        $this->branchUser = User::factory()->create(['branch_id' => $this->branch1->id]);
        $this->branchUser->assignRole('User');

        $this->position = Position::create(['name' => 'Engineer']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'branch_id' => $this->branch1->id,
            'category' => 'Мутахассис',
            'type_id' => 'штатный',
            'full_name' => 'New Employee',
            'gender' => 'мужской',
            'position_id' => $this->position->id,
            'hire_date' => '2020-01-01',
        ], $overrides);
    }

    public function test_admin_can_create_employee(): void
    {
        $response = $this->actingAs($this->admin)->post(route('employees.store'), $this->payload());

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'full_name' => 'New Employee',
            'branch_id' => $this->branch1->id,
            'employment_type' => 'штатный',
        ]);
    }

    public function test_branch_user_cannot_create_in_other_branch(): void
    {
        $response = $this->actingAs($this->branchUser)
            ->post(route('employees.store'), $this->payload(['branch_id' => $this->branch2->id, 'full_name' => 'Wrong']));

        $response->assertStatus(403);
        $this->assertDatabaseMissing('employees', ['full_name' => 'Wrong']);
    }

    public function test_free_text_lookups_are_resolved_to_ids(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.store'), $this->payload(['nationality' => 'Тоҷик']))
            ->assertRedirect();

        $this->assertDatabaseHas('nationalities', ['name' => 'Тоҷик']);

        $employee = Employee::where('full_name', 'New Employee')->first();
        $this->assertSame(Nationality::where('name', 'Тоҷик')->value('id'), $employee->nationality_id);
    }

    public function test_admin_can_update_employee(): void
    {
        $employee = Employee::create($this->payload(['employment_type' => 'штатный']) + ['gender' => 'мужской']);

        $this->actingAs($this->admin)
            ->put(route('employees.update', $employee), $this->payload(['full_name' => 'Renamed']))
            ->assertRedirect(route('employees.index'));

        $this->assertSame('Renamed', $employee->fresh()->full_name);
    }

    public function test_branch_user_cannot_update_other_branch_employee(): void
    {
        $employee = Employee::create([
            'branch_id' => $this->branch2->id,
            'category' => 'Мутахассис',
            'employment_type' => 'штатный',
            'full_name' => 'Foreign',
            'gender' => 'мужской',
            'position_id' => $this->position->id,
            'hire_date' => '2020-01-01',
        ]);

        $this->actingAs($this->branchUser)
            ->put(route('employees.update', $employee), $this->payload(['branch_id' => $this->branch2->id]))
            ->assertStatus(403);
    }

    public function test_database_rejects_an_invalid_category(): void
    {
        // The CHECK constraint guards the enum at the DB level, even against a
        // raw insert that bypasses the model cast / form validation.
        $this->expectException(QueryException::class);

        DB::table('employees')->insert([
            'branch_id' => $this->branch1->id,
            'full_name' => 'Bad Category',
            'category' => 'НесуществующаяКатегория',
        ]);
    }

    public function test_branch_user_can_delete_employee_of_own_branch(): void
    {
        // The employee is resolved from the DB via route binding, so its
        // branch_id reflects the stored type — the policy's strict comparison
        // would 403 on the user's own branch without the integer cast.
        $employee = Employee::create([
            'branch_id' => $this->branch1->id,
            'category' => 'Мутахассис',
            'employment_type' => 'штатный',
            'full_name' => 'Own Branch',
            'gender' => 'мужской',
            'position_id' => $this->position->id,
            'hire_date' => '2020-01-01',
        ]);

        $this->actingAs($this->branchUser)
            ->delete(route('employees.destroy', $employee))
            ->assertRedirect(route('employees.index'));

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_admin_can_delete_employee(): void
    {
        $employee = Employee::create([
            'branch_id' => $this->branch1->id,
            'category' => 'Мутахассис',
            'employment_type' => 'штатный',
            'full_name' => 'Deletable',
            'gender' => 'мужской',
            'position_id' => $this->position->id,
            'hire_date' => '2020-01-01',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('employees.destroy', $employee))
            ->assertRedirect(route('employees.index'));

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_admin_can_filter_employees_by_branch_type_and_search(): void
    {
        $this->makeEmployee($this->branch1->id, 'Alice Permanent', ['employment_type' => 'штатный']);
        $this->makeEmployee($this->branch1->id, 'Bob Contractor', ['employment_type' => 'контракт']);
        $this->makeEmployee($this->branch2->id, 'Carol Other', ['employment_type' => 'штатный']);

        // Filter by branch.
        $this->actingAs($this->admin)
            ->get(route('employees.index', ['filter' => ['branch_id' => $this->branch1->id]]))
            ->assertInertia(fn ($page) => $page->has('employees.data', 2));

        // Filter by employment type (the type_id param maps to employment_type).
        $this->actingAs($this->admin)
            ->get(route('employees.index', ['filter' => ['type_id' => 'контракт']]))
            ->assertInertia(fn ($page) => $page
                ->has('employees.data', 1)
                ->where('employees.data.0.full_name', 'Bob Contractor'));

        // Search by name (the search scope).
        $this->actingAs($this->admin)
            ->get(route('employees.index', ['filter' => ['search' => 'Alice']]))
            ->assertInertia(fn ($page) => $page
                ->has('employees.data', 1)
                ->where('employees.data.0.full_name', 'Alice Permanent'));
    }

    public function test_duplicate_inn_is_rejected(): void
    {
        Employee::create([
            'branch_id' => $this->branch1->id,
            'category' => 'Мутахассис',
            'employment_type' => 'штатный',
            'full_name' => 'First',
            'gender' => 'мужской',
            'position_id' => $this->position->id,
            'hire_date' => '2020-01-01',
            'inn' => '123456',
        ]);

        $this->actingAs($this->admin)
            ->from(route('employees.index'))
            ->post(route('employees.store'), $this->payload(['inn' => '123456']))
            ->assertSessionHasErrors('inn');
    }

    public function test_email_is_stored_when_creating_employee(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.store'), $this->payload(['email' => 'john@example.com']))
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'full_name' => 'New Employee',
            'email' => 'john@example.com',
        ]);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        Employee::create([
            'branch_id' => $this->branch1->id,
            'category' => 'Мутахассис',
            'employment_type' => 'штатный',
            'full_name' => 'First',
            'gender' => 'мужской',
            'position_id' => $this->position->id,
            'hire_date' => '2020-01-01',
            'email' => 'taken@example.com',
        ]);

        $this->actingAs($this->admin)
            ->from(route('employees.index'))
            ->post(route('employees.store'), $this->payload(['email' => 'taken@example.com']))
            ->assertSessionHasErrors('email');
    }

    public function test_out_of_range_year_is_rejected_without_crashing(): void
    {
        // An absurd year previously 500'd (Carbon getTimestamp overflow) inside
        // the native after_or_equal/date rules. It must now fail cleanly (422).
        $this->actingAs($this->admin)
            ->from(route('employees.index'))
            ->post(route('employees.store'), $this->payload(['dismissal_date' => '999999-01-01']))
            ->assertSessionHasErrors('dismissal_date');

        // Above the 3000 cap is also rejected.
        $this->actingAs($this->admin)
            ->from(route('employees.index'))
            ->post(route('employees.store'), $this->payload(['dismissal_date' => '3500-06-01']))
            ->assertSessionHasErrors('dismissal_date');

        $this->assertDatabaseMissing('employees', ['full_name' => 'New Employee']);
    }

    public function test_dismissal_date_before_hire_date_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->from(route('employees.index'))
            ->post(route('employees.store'), $this->payload([
                'hire_date' => '2020-01-01',
                'dismissal_date' => '2019-01-01',
            ]))
            ->assertSessionHasErrors('dismissal_date');
    }

    public function test_dismissal_requires_a_reason(): void
    {
        $this->actingAs($this->admin)
            ->from(route('employees.index'))
            ->post(route('employees.store'), $this->payload(['dismissal_date' => '2024-01-01']))
            ->assertSessionHasErrors('dismissal_reason');

        $this->assertDatabaseMissing('employees', ['full_name' => 'New Employee']);
    }

    public function test_dismissal_reason_is_stored(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.store'), $this->payload([
                'dismissal_date' => '2024-01-01',
                'dismissal_reason' => 'Бо хоҳиши худ',
            ]))
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'full_name' => 'New Employee',
            'dismissal_reason' => 'Бо хоҳиши худ',
        ]);
    }

    public function test_admin_can_restore_archived_employee(): void
    {
        $employee = Employee::create([
            'branch_id' => $this->branch1->id,
            'category' => 'Мутахассис',
            'employment_type' => 'штатный',
            'full_name' => 'Archived',
            'gender' => 'мужской',
            'position_id' => $this->position->id,
            'hire_date' => '2020-01-01',
            'dismissal_date' => '2024-01-01',
            'dismissal_reason' => 'Ихтисор',
        ]);

        $this->actingAs($this->admin)
            ->post(route('employees.restore', $employee))
            ->assertRedirect(route('employees.archive'));

        $fresh = $employee->fresh();
        $this->assertNull($fresh->dismissal_date);
        $this->assertNull($fresh->dismissal_reason);
    }

    public function test_branch_user_cannot_restore_other_branch_employee(): void
    {
        $employee = Employee::create([
            'branch_id' => $this->branch2->id,
            'category' => 'Мутахассис',
            'employment_type' => 'штатный',
            'full_name' => 'Foreign Archived',
            'gender' => 'мужской',
            'position_id' => $this->position->id,
            'hire_date' => '2020-01-01',
            'dismissal_date' => '2024-01-01',
        ]);

        $this->actingAs($this->branchUser)
            ->post(route('employees.restore', $employee))
            ->assertStatus(403);

        $this->assertNotNull($employee->fresh()->dismissal_date);
    }

    public function test_creating_employee_writes_a_single_named_log(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.store'), $this->payload(['full_name' => 'Solo Log']));

        $logs = Activity::query()
            ->where('subject_type', Employee::class)
            ->where('event', 'created')
            ->where('description', 'like', '%Solo Log%')
            ->get();

        // Exactly one entry (no auto + manual duplicate), and it carries the name.
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('Корманд илова шуд', $logs->first()->description);
    }

    public function test_branch_user_cannot_force_delete_other_branch_trashed_employee(): void
    {
        $employee = Employee::create([
            'branch_id' => $this->branch2->id,
            'category' => 'Мутахассис',
            'employment_type' => 'штатный',
            'full_name' => 'Foreign Trashed',
            'gender' => 'мужской',
            'position_id' => $this->position->id,
            'hire_date' => '2020-01-01',
        ]);
        $employee->delete();

        $this->actingAs($this->branchUser)
            ->delete(route('trash.employees.force', $employee->id))
            ->assertStatus(403);

        // Still only soft-deleted, not permanently removed.
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_force_delete_from_trash_logs_who_was_removed(): void
    {
        $employee = Employee::create([
            'branch_id' => $this->branch1->id,
            'category' => 'Мутахассис',
            'employment_type' => 'штатный',
            'full_name' => 'Trash Victim',
            'gender' => 'мужской',
            'position_id' => $this->position->id,
            'hire_date' => '2020-01-01',
        ]);
        $employee->delete(); // soft-delete → trash

        $this->actingAs($this->admin)
            ->delete(route('trash.employees.force', $employee->id))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_log', [
            'event' => 'deleted',
            'description' => 'Корманд аз сабад тамоман нест карда шуд: Trash Victim',
        ]);
    }

    public function test_rotation_moves_employee_and_records_history(): void
    {
        $employee = Employee::create([
            'branch_id' => $this->branch1->id,
            'category' => 'Мутахассис',
            'employment_type' => 'штатный',
            'full_name' => 'Mover',
            'gender' => 'мужской',
            'position_id' => $this->position->id,
            'hire_date' => '2020-01-01',
        ]);

        $newPosition = Position::create(['name' => 'Senior Engineer']);

        $this->actingAs($this->admin)
            ->post(route('employees.rotate', $employee), [
                'branch_id' => $this->branch2->id,
                'position_id' => $newPosition->id,
                'rotation_date' => '2024-01-01',
                'reason' => 'Promotion',
            ])
            ->assertRedirect();

        $employee->refresh();
        $this->assertEquals($this->branch2->id, $employee->branch_id);
        $this->assertEquals($newPosition->id, $employee->position_id);
        $this->assertDatabaseHas('rotations', [
            'employee_id' => $employee->id,
            'old_branch_id' => $this->branch1->id,
            'new_branch_id' => $this->branch2->id,
            'new_position_id' => $newPosition->id,
        ]);
    }

    private function makeEmployee(int $branchId, string $name, array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'branch_id' => $branchId,
            'category' => 'Мутахассис',
            'employment_type' => 'штатный',
            'full_name' => $name,
            'gender' => 'мужской',
            'position_id' => $this->position->id,
            'hire_date' => '2020-01-01',
        ], $overrides));
    }

    public function test_manager_from_other_branch_is_rejected(): void
    {
        $foreignManager = $this->makeEmployee($this->branch2->id, 'Foreign Manager');

        $this->actingAs($this->admin)
            ->from(route('employees.index'))
            ->post(route('employees.store'), $this->payload(['manager_id' => $foreignManager->id]))
            ->assertSessionHasErrors('manager_id');
    }

    public function test_soft_deleted_manager_is_rejected(): void
    {
        $manager = $this->makeEmployee($this->branch1->id, 'Gone Manager');
        $manager->delete();

        $this->actingAs($this->admin)
            ->from(route('employees.index'))
            ->post(route('employees.store'), $this->payload(['manager_id' => $manager->id]))
            ->assertSessionHasErrors('manager_id');
    }

    public function test_same_branch_manager_is_accepted(): void
    {
        $manager = $this->makeEmployee($this->branch1->id, 'Good Manager');

        $this->actingAs($this->admin)
            ->post(route('employees.store'), $this->payload(['full_name' => 'Has Manager', 'manager_id' => $manager->id]))
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', ['full_name' => 'Has Manager', 'manager_id' => $manager->id]);
    }

    public function test_employee_cannot_be_its_own_manager_on_update(): void
    {
        $employee = $this->makeEmployee($this->branch1->id, 'Selfie');

        $this->actingAs($this->admin)
            ->from(route('employees.index'))
            ->put(route('employees.update', $employee), $this->payload(['full_name' => 'Selfie', 'manager_id' => $employee->id]))
            ->assertSessionHasErrors('manager_id');
    }

    public function test_duplicate_sin_is_rejected(): void
    {
        $this->makeEmployee($this->branch1->id, 'First Sin', ['sin' => 'SIN-1']);

        $this->actingAs($this->admin)
            ->from(route('employees.index'))
            ->post(route('employees.store'), $this->payload(['sin' => 'SIN-1']))
            ->assertSessionHasErrors('sin');
    }

    public function test_empty_sin_can_repeat(): void
    {
        $this->makeEmployee($this->branch1->id, 'No Sin One');

        $this->actingAs($this->admin)
            ->post(route('employees.store'), $this->payload(['full_name' => 'No Sin Two']))
            ->assertRedirect(route('employees.index'));

        $this->assertSame(2, Employee::whereNull('sin')->count());
    }

    public function test_force_delete_preserves_rotation_history_and_nulls_employee(): void
    {
        $employee = $this->makeEmployee($this->branch1->id, 'Rotater');
        $newPosition = Position::create(['name' => 'Lead']);

        $this->actingAs($this->admin)
            ->post(route('employees.rotate', $employee), [
                'branch_id' => $this->branch2->id,
                'position_id' => $newPosition->id,
                'rotation_date' => '2024-01-01',
                'reason' => 'Promotion',
            ])
            ->assertRedirect();

        $rotationId = Rotation::where('employee_id', $employee->id)->value('id');
        $this->assertNotNull($rotationId);

        $employee->delete(); // soft-delete → trash
        $this->actingAs($this->admin)
            ->delete(route('trash.employees.force', $employee->id))
            ->assertRedirect();

        // The employee row is gone, but the rotation history survives with a
        // nulled employee_id and its movement fields intact.
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
        $this->assertDatabaseHas('rotations', [
            'id' => $rotationId,
            'employee_id' => null,
            'new_branch_id' => $this->branch2->id,
            'new_position_id' => $newPosition->id,
        ]);
    }

    public function test_unchanged_archived_manager_does_not_block_update(): void
    {
        $manager = $this->makeEmployee($this->branch1->id, 'The Manager');
        $employee = $this->makeEmployee($this->branch1->id, 'Subordinate', ['manager_id' => $manager->id]);
        $manager->delete(); // archive the manager; subordinate still points at it

        $this->actingAs($this->admin)
            ->put(route('employees.update', $employee), $this->payload([
                'full_name' => 'Subordinate Renamed',
                'manager_id' => $manager->id, // unchanged → must not be re-validated
            ]))
            ->assertRedirect(route('employees.index'));

        $this->assertSame('Subordinate Renamed', $employee->fresh()->full_name);
    }

    public function test_moving_to_another_branch_revalidates_existing_manager(): void
    {
        $manager = $this->makeEmployee($this->branch1->id, 'B1 Manager');
        $employee = $this->makeEmployee($this->branch1->id, 'Mover', ['manager_id' => $manager->id]);

        // Branch changes but the (branch-1) manager is kept → must be re-validated
        // and rejected, not slip through the "unchanged manager" bypass.
        $this->actingAs($this->admin)
            ->from(route('employees.index'))
            ->put(route('employees.update', $employee), $this->payload([
                'branch_id' => $this->branch2->id,
                'full_name' => 'Mover',
                'manager_id' => $manager->id,
            ]))
            ->assertSessionHasErrors('manager_id');
    }

    public function test_restore_blocked_when_unique_identifier_reused(): void
    {
        $original = $this->makeEmployee($this->branch1->id, 'Original', ['inn' => 'INN-DUP']);
        $original->delete(); // to trash → frees the INN (partial unique)
        $this->makeEmployee($this->branch1->id, 'Reuser', ['inn' => 'INN-DUP']);

        $this->actingAs($this->admin)
            ->from(route('trash.index'))
            ->post(route('trash.employees.restore', $original->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        // Restore must be refused, not crash with a raw unique violation.
        $this->assertSoftDeleted('employees', ['id' => $original->id]);
    }
}
