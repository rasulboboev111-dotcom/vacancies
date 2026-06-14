<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApplicationWebTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $branchUser;

    private Branch $branch1;

    private Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('intake.disk'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $userRole = Role::firstOrCreate(['name' => 'User']);

        foreach ([
            'view applications',
            'create applications',
            'edit applications',
            'delete applications',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $adminRole->syncPermissions(Permission::all());
        $userRole->syncPermissions([
            'view applications',
            'create applications',
            'edit applications',
            'delete applications',
        ]);

        $this->branch1 = Branch::create(['name' => 'Branch One', 'code' => 'B1']);
        $this->branch2 = Branch::create(['name' => 'Branch Two', 'code' => 'B2']);

        $this->admin = User::factory()->create(['branch_id' => null]);
        $this->admin->assignRole('Admin');

        $this->branchUser = User::factory()->create(['branch_id' => $this->branch1->id]);
        $this->branchUser->assignRole('User');
    }

    private function makeApplication(array $overrides = []): Application
    {
        return Application::create(array_merge([
            'branch_id' => $this->branch1->id,
            'name' => 'Test Applicant',
            'source' => 'manual',
        ], $overrides));
    }

    public function test_branch_user_sees_only_their_branch(): void
    {
        $appA = $this->makeApplication(['branch_id' => $this->branch1->id, 'name' => 'Branch A App']);
        $this->makeApplication(['branch_id' => $this->branch2->id, 'name' => 'Branch B App']);
        $this->makeApplication(['branch_id' => null, 'name' => 'Unassigned App']);

        $this->actingAs($this->branchUser)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Applications/Index')
                ->has('applications.data', 1)
                ->where('applications.data.0.id', $appA->id));
    }

    public function test_admin_sees_all_including_unassigned(): void
    {
        $this->makeApplication(['branch_id' => $this->branch1->id]);
        $this->makeApplication(['branch_id' => $this->branch2->id]);
        $this->makeApplication(['branch_id' => null]);

        $this->actingAs($this->admin)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Applications/Index')
                ->has('applications.data', 3));
    }

    public function test_viewany_denied_without_permission(): void
    {
        // User with branch but no role assigned → no permissions at all → 403
        $userWithoutPermission = User::factory()->create(['branch_id' => $this->branch1->id]);

        $this->actingAs($userWithoutPermission)
            ->get(route('applications.index'))
            ->assertForbidden();
    }

    public function test_branch_user_store_forces_own_branch_and_saves_resume(): void
    {
        $resume = UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

        $this->actingAs($this->branchUser)
            ->post(route('applications.store'), [
                'branch_id' => $this->branch2->id, // attempt to target other branch — must be forced to branch1
                'name' => 'New Applicant',
                'phone' => '+992901234567',
                'resume' => $resume,
            ])
            ->assertRedirect();

        $app = Application::where('name', 'New Applicant')->firstOrFail();
        $this->assertEquals($this->branch1->id, $app->branch_id);
        $this->assertNotNull($app->resume_path);
        Storage::disk(config('intake.disk'))->assertExists($app->resume_path);
    }

    public function test_update_changes_contact_fields(): void
    {
        $application = $this->makeApplication(['name' => 'Original Name', 'phone' => '+992900000000']);

        $this->actingAs($this->branchUser)
            ->put(route('applications.update', $application->id), [
                'name' => 'Updated Name',
                'phone' => '+992911111111',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'name' => 'Updated Name',
            'phone' => '+992911111111',
        ]);
    }

    public function test_destroy_soft_deletes(): void
    {
        $application = $this->makeApplication();

        $this->actingAs($this->branchUser)
            ->delete(route('applications.destroy', $application->id))
            ->assertRedirect();

        $this->assertSoftDeleted('applications', ['id' => $application->id]);
    }

    public function test_download_resume_blocks_cross_branch(): void
    {
        // Application with resume belonging to branch2
        $branchBUser = User::factory()->create(['branch_id' => $this->branch2->id]);
        $branchBUser->assignRole('User');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $resumePath = 'resumes/app_test.pdf';
        Storage::disk(config('intake.disk'))->put($resumePath, 'fake-pdf-content');

        $appB = Application::create([
            'branch_id' => $this->branch2->id,
            'name' => 'Branch B Applicant',
            'source' => 'manual',
            'resume_path' => $resumePath,
            'resume_filename' => 'cv.pdf',
        ]);

        // Branch A user cannot download branch B's resume → 403
        $this->actingAs($this->branchUser)
            ->get(route('applications.resume', $appB->id))
            ->assertForbidden();

        // Branch B user can download their own branch's resume → 200
        $this->actingAs($branchBUser)
            ->get(route('applications.resume', $appB->id))
            ->assertOk();

        // Admin can download any resume → 200
        $this->actingAs($this->admin)
            ->get(route('applications.resume', $appB->id))
            ->assertOk();
    }
}
