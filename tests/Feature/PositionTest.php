<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PositionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $branchUser;

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

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');

        $this->branchUser = User::factory()->create();
        $this->branchUser->assignRole('User');
    }

    public function test_user_cannot_create_position(): void
    {
        $response = $this
            ->actingAs($this->branchUser)
            ->post(route('positions.store'), [
                'name' => 'New Position',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_position(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->post(route('positions.store'), [
                'name' => 'Developer',
            ]);

        $response->assertRedirect(route('positions.index'));
        $this->assertDatabaseHas('positions', ['name' => 'Developer']);
    }

    public function test_position_name_is_trimmed(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->post(route('positions.store'), [
                'name' => '   Developer   ',
            ]);

        $response->assertRedirect(route('positions.index'));
        $this->assertDatabaseHas('positions', ['name' => 'Developer']);
    }

    public function test_duplicate_position_name_fails_validation(): void
    {
        Position::create(['name' => 'Developer']);

        $response = $this
            ->actingAs($this->admin)
            ->from(route('positions.index'))
            ->post(route('positions.store'), [
                'name' => '  developer  ',
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertCount(1, Position::all());
    }

    public function test_updating_position_with_own_name_passes(): void
    {
        $position = Position::create(['name' => 'Developer']);

        $response = $this
            ->actingAs($this->admin)
            ->put(route('positions.update', $position->id), [
                'name' => 'Developer',
            ]);

        $response->assertRedirect(route('positions.index'));
    }

    public function test_updating_position_with_duplicate_name_fails(): void
    {
        $position1 = Position::create(['name' => 'Developer']);
        $position2 = Position::create(['name' => 'Designer']);

        $response = $this
            ->actingAs($this->admin)
            ->put(route('positions.update', $position2->id), [
                'name' => '  DEVELOPER  ',
            ]);

        $response->assertSessionHasErrors('name');
    }
}
