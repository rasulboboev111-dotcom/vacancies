<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_employees_lacking_a_category(): void
    {
        Role::firstOrCreate(['name' => 'Admin']);
        $admin = User::factory()->create(['branch_id' => null]);
        $admin->assignRole('Admin');

        $branch = Branch::create(['name' => 'Branch', 'code' => 'B1']);

        // A null category makes the dashboard's COALESCE substitute the
        // placeholder string — which must not be run through the Category enum
        // cast (that previously 500'd the page).
        Employee::create(['branch_id' => $branch->id, 'full_name' => 'No Category']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_dashboard_reports_gender_statistics_overall_and_for_managers(): void
    {
        Role::firstOrCreate(['name' => 'Admin']);
        $admin = User::factory()->create(['branch_id' => null]);
        $admin->assignRole('Admin');

        $branch = Branch::create(['name' => 'Branch', 'code' => 'B1']);

        // Managers are determined by the "Роҳбарият" category, not by heading a department.
        Employee::create(['branch_id' => $branch->id, 'full_name' => 'Male Manager', 'gender' => 'мужской', 'category' => 'Роҳбарият']);
        Employee::create(['branch_id' => $branch->id, 'full_name' => 'Female Manager', 'gender' => 'женский', 'category' => 'Роҳбарият']);
        Employee::create(['branch_id' => $branch->id, 'full_name' => 'Male Staff', 'gender' => 'мужской', 'category' => 'Мутахассис']);
        Employee::create(['branch_id' => $branch->id, 'full_name' => 'No Gender']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('stats.gender_stats.male', 2)
                ->where('stats.gender_stats.female', 1)
                ->where('stats.gender_stats.unspecified', 1)
                ->where('stats.manager_gender_stats.male', 1)
                ->where('stats.manager_gender_stats.female', 1)
                ->where('stats.manager_gender_stats.unspecified', 0)
                ->etc()
            );
    }

    public function test_dashboard_statistics_exclude_archived_employees(): void
    {
        Role::firstOrCreate(['name' => 'Admin']);
        $admin = User::factory()->create(['branch_id' => null]);
        $admin->assignRole('Admin');

        $branch = Branch::create(['name' => 'Branch', 'code' => 'B1']);

        Employee::create(['branch_id' => $branch->id, 'full_name' => 'Active', 'gender' => 'мужской', 'category' => 'Роҳбарият']);
        // Dismissed (archived) — must not be counted anywhere on the dashboard.
        Employee::create(['branch_id' => $branch->id, 'full_name' => 'Dismissed', 'gender' => 'женский', 'category' => 'Роҳбарият', 'dismissal_date' => '2024-01-01']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('stats.total_employees', 1)
                ->where('stats.gender_stats.male', 1)
                ->where('stats.gender_stats.female', 0)
                ->where('stats.manager_gender_stats.male', 1)
                ->where('stats.manager_gender_stats.female', 0)
                ->etc()
            );
    }

    public function test_branch_user_dashboard_is_scoped_to_their_branch(): void
    {
        Role::firstOrCreate(['name' => 'User']);

        $own = Branch::create(['name' => 'Own', 'code' => 'OWN']);
        $other = Branch::create(['name' => 'Other', 'code' => 'OTH']);

        Employee::create(['branch_id' => $own->id, 'full_name' => 'Own Manager', 'gender' => 'мужской', 'category' => 'Роҳбарият']);
        Employee::create(['branch_id' => $other->id, 'full_name' => 'Other Manager', 'gender' => 'женский', 'category' => 'Роҳбарият']);

        $user = User::factory()->create(['branch_id' => $own->id]);
        $user->assignRole('User');
        $user = User::findOrFail($user->id);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('stats.total_employees', 1)
                ->where('stats.total_branches', 1)
                ->where('stats.gender_stats.male', 1)
                ->where('stats.gender_stats.female', 0)
                ->where('stats.manager_gender_stats.male', 1)
                ->where('stats.manager_gender_stats.female', 0)
                ->etc()
            );
    }
}
