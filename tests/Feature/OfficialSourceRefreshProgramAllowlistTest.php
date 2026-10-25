<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\OfficialSourceRefreshEvent;
use App\Services\OfficialSourceCanonicalRefreshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

use App\Models\Program;

class OfficialSourceRefreshProgramAllowlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_allowlisted_program_field_refresh_writes_audit_event(): void
    {
        $institution = Institution::create([
            'name' => 'Refresh Model Coverage University',
            'slug' => 'refresh-model-coverage-university',
            'status' => 'active',
        ]);
        $program = Program::create([
            'institution_id' => $institution->id,
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'program_level' => 'undergraduate',
            'status' => 'active',
            'description' => 'Old official description',
        ]);

        $updated = app(OfficialSourceCanonicalRefreshService::class)->refresh(
            $program, 'description', 'Old official description', 'Updated official description', $this->validEvidence()
        );

        $this->assertSame('Updated official description', $updated->fresh()->description);
        $this->assertDatabaseCount('official_source_refresh_events', 1);
        $event = OfficialSourceRefreshEvent::query()->firstOrFail();
        $this->assertSame(Program::class, $event->canonical_type);
        $this->assertSame((int) $program->id, (int) $event->canonical_id);
        $this->assertSame('description', $event->field_name);
        $this->assertSame(['value' => 'Old official description'], $event->before_value);
        $this->assertSame(['value' => 'Updated official description'], $event->after_value);
    }

    private function validEvidence(): array
    {
        return [
            'source_id' => null,
            'source_change_observation_id' => null,
            'source_official' => true,
            'source_identity_verified' => true,
            'fetch_successful' => true,
            'entity_match_confirmed' => true,
            'evidence_url' => 'https://university.example.edu/programs',
            'evidence_excerpt' => 'The official page confirms the updated program detail.',
            'observed_at' => '2026-10-25T04:00:00Z',
            'content_fingerprint' => hash('sha256', 'official program page'),
        ];
    }
}
