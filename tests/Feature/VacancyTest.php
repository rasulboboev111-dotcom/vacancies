<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VacancyTest extends TestCase
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
            'view vacancies',
            'create vacancies',
            'edit vacancies',
            'delete vacancies',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $adminRole->syncPermissions(Permission::all());
        $userRole->syncPermissions([
            'view vacancies',
            'create vacancies',
            'edit vacancies',
            'delete vacancies',
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

    public function test_admin_can_create_vacancy(): void
    {
        $response = $this->actingAs($this->admin)->post(route('vacancies.store'), [
            'branch_id' => $this->branch1->id,
            'title' => 'Инженер связи',
            'schedule' => '5/2',
            'salary' => '5000 сомони',
            'requirements' => 'Высшее образование',
        ]);

        $response->assertRedirect(route('vacancies.index', ['branch_id' => $this->branch1->id]));
        $this->assertDatabaseHas('vacancies', [
            'branch_id' => $this->branch1->id,
            'title' => 'Инженер связи',
            'status' => 'open',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_branch_user_creates_vacancy_in_own_branch(): void
    {
        $response = $this->actingAs($this->branchUser)->post(route('vacancies.store'), [
            'title' => 'Оператор',
        ]);

        $response->assertRedirect(route('vacancies.index'));
        $this->assertDatabaseHas('vacancies', [
            'branch_id' => $this->branch1->id,
            'title' => 'Оператор',
            'created_by' => $this->branchUser->id,
        ]);
    }

    public function test_branch_user_cannot_create_in_other_branch(): void
    {
        $response = $this->actingAs($this->branchUser)->post(route('vacancies.store'), [
            'branch_id' => $this->branch2->id,
            'title' => 'Foreign Vacancy',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('vacancies', ['title' => 'Foreign Vacancy']);
    }

    public function test_user_without_branch_cannot_create(): void
    {
        $response = $this->actingAs($this->userWithoutBranch)->post(route('vacancies.store'), [
            'title' => 'No Branch Vacancy',
        ]);

        $response->assertStatus(403);
    }

    public function test_closing_a_vacancy_sets_closed_at(): void
    {
        $vacancy = Vacancy::create([
            'branch_id' => $this->branch1->id,
            'title' => 'To Close',
            'status' => 'open',
            'opened_at' => now()->toDateString(),
        ]);

        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->branchUser)->put(route('vacancies.update', $vacancy), [
            'title' => 'To Close',
            'status' => 'closed',
        ]);

        $response->assertRedirect();
        $vacancy->refresh();
        $this->assertSame('closed', $vacancy->status);
        $this->assertNotNull($vacancy->closed_at);
    }

    public function test_branch_user_cannot_update_other_branch_vacancy(): void
    {
        $vacancy = Vacancy::create([
            'branch_id' => $this->branch2->id,
            'title' => 'Other Branch',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->branchUser)->put(route('vacancies.update', $vacancy), [
            'title' => 'Hacked',
            'status' => 'closed',
        ]);

        $response->assertStatus(403);
    }

    public function test_branch_user_only_sees_own_branch_vacancies(): void
    {
        Vacancy::create(['branch_id' => $this->branch1->id, 'title' => 'Mine', 'status' => 'open']);
        Vacancy::create(['branch_id' => $this->branch2->id, 'title' => 'Theirs', 'status' => 'open']);

        $response = $this->actingAs($this->branchUser)->get(route('vacancies.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Vacancies/Index')
            ->has('vacancies', 1)
            ->where('vacancies.0.title', 'Mine'));
    }

    public function test_admin_can_delete_vacancy(): void
    {
        $vacancy = Vacancy::create([
            'branch_id' => $this->branch1->id,
            'title' => 'Deletable',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('vacancies.destroy', $vacancy));

        $response->assertRedirect();
        $this->assertSoftDeleted('vacancies', ['id' => $vacancy->id]);
    }
}
