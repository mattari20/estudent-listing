<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\OfficialSourceRefreshEvent;
use App\Services\OfficialSourceCanonicalRefreshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

use App\Models\Program;

class OfficialSourceRefreshProgramFieldGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_slug_is_not_allowlisted_for_official_refresh(): void
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
            'status' => 'active',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not allowlisted');
        try {
            app(OfficialSourceCanonicalRefreshService::class)->refresh(
                $program, 'slug', 'computer-science', 'new-slug', $this->validEvidence()
            );
        } finally {
            $this->assertSame('computer-science', $program->fresh()->slug);
            $this->assertDatabaseCount('official_source_refresh_events', 0);
        }
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
