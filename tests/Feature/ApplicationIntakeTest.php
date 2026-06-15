<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Branch;
use App\Models\Position;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationIntakeTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey = 'test-secret-key-for-intake';

    protected function setUp(): void
    {
        parent::setUp();

        config(['intake.api_key' => $this->apiKey]);

        Storage::fake('local');
    }

    private function payload(array $overrides = []): string
    {
        return json_encode(array_merge([
            'external_id' => 123,
            'name' => 'Алишер Назаров',
            'email' => 'alisher@example.com',
            'phone' => '+992901234567',
            'vacancy' => null,
            'source' => 'telegram',
            'summary' => 'Опытный специалист',
            'survey' => null,
            'created_at' => '2026-06-01T10:00:00Z',
        ], $overrides));
    }

    public function test_rejects_without_or_with_wrong_api_key(): void
    {
        // No header → 401
        $this->postJson('/api/applications', ['payload' => $this->payload()])
            ->assertStatus(401);

        // Wrong key → 401
        $this->withHeaders(['X-Api-Key' => 'wrong-key'])
            ->postJson('/api/applications', ['payload' => $this->payload()])
            ->assertStatus(401);

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_creates_unresolved_application_and_returns_id(): void
    {
        $file = UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->post('/api/applications', [
                'payload' => $this->payload(['vacancy' => null]),
                'resume' => $file,
            ]);

        $response->assertStatus(200)->assertJsonStructure(['id']);

        $this->assertDatabaseHas('applications', [
            'external_id' => 123,
            'branch_id' => null,
        ]);

        $app = Application::where('external_id', 123)->firstOrFail();
        $media = $app->getFirstMedia('resumes');
        $this->assertNotNull($media);
        $this->assertSame('cv.pdf', $media->file_name);
    }

    public function test_resolves_branch_from_matching_vacancy(): void
    {
        $branch = Branch::create(['name' => 'Головной офис', 'code' => 'HQ']);
        $position = Position::firstOrCreate(['name' => 'QA инженер']);
        $vacancy = Vacancy::create([
            'branch_id' => $branch->id,
            'position_id' => $position->id,
            'title' => 'QA инженер',
            'status' => 'open',
            'opened_at' => now()->toDateString(),
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->postJson('/api/applications', [
                'payload' => $this->payload(['vacancy' => 'QA инженер']),
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('applications', [
            'external_id' => 123,
            'branch_id' => $branch->id,
            'vacancy_id' => $vacancy->id,
        ]);
    }

    public function test_second_push_updates_same_row_with_survey(): void
    {
        // Phase 1 — no survey
        $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->postJson('/api/applications', [
                'payload' => $this->payload(['survey' => null]),
            ])
            ->assertStatus(200);

        $this->assertDatabaseCount('applications', 1);

        // Phase 2 — same external_id, survey filled in
        $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->postJson('/api/applications', [
                'payload' => $this->payload(['survey' => ['experience' => '3']]),
            ])
            ->assertStatus(200);

        $this->assertDatabaseCount('applications', 1);

        $app = Application::first();
        $this->assertIsArray($app->survey);
        $this->assertSame('3', $app->survey['experience']);
    }

    public function test_rejects_malformed_payload_json(): void
    {
        $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->postJson('/api/applications', [
                'payload' => 'not-json{',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_rejects_disallowed_file_type(): void
    {
        $file = UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream');

        $this->withHeaders(['X-Api-Key' => $this->apiKey, 'Accept' => 'application/json'])
            ->post('/api/applications', [
                'payload' => $this->payload(),
                'resume' => $file,
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('applications', 0);
    }
}
