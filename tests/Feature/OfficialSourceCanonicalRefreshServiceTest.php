<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Institution;
use App\Models\OfficialSourceRefreshEvent;
use App\Services\OfficialSourceCanonicalRefreshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OfficialSourceCanonicalRefreshServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_evidence_updates_allowlisted_field_and_writes_audit_event(): void
    {
        $institution = Institution::create([
            'name' => 'Official Refresh University',
            'slug' => 'official-refresh-university',
            'status' => 'active',
        ]);
        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Old Campus Name',
            'slug' => 'old-campus-name',
            'status' => 'active',
        ]);

        $updated = app(OfficialSourceCanonicalRefreshService::class)->refresh(
            $campus,
            'name',
            'Old Campus Name',
            'Updated Campus Name',
            $this->validEvidence()
        );

        $this->assertSame('Updated Campus Name', $updated->fresh()->name);
        $this->assertDatabaseCount('official_source_refresh_events', 1);
        $event = OfficialSourceRefreshEvent::query()->firstOrFail();
        $this->assertSame('name', $event->field_name);
        $this->assertSame(['value' => 'Old Campus Name'], $event->before_value);
        $this->assertSame(['value' => 'Updated Campus Name'], $event->after_value);
    }

    public function test_stale_expected_value_blocks_refresh_and_preserves_canonical_value(): void
    {
        $institution = Institution::create([
            'name' => 'Stale Evidence University',
            'slug' => 'stale-evidence-university',
            'status' => 'active',
        ]);
        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Newer Campus Name',
            'slug' => 'newer-campus-name',
            'status' => 'active',
        ]);

        try {
            app(OfficialSourceCanonicalRefreshService::class)->refresh(
                $campus,
                'name',
                'Old reviewed value',
                'Stale proposed value',
                $this->validEvidence()
            );
            $this->fail('Stale proposal should be blocked.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('changed after review', $exception->getMessage());
        }

        $this->assertSame('Newer Campus Name', $campus->fresh()->name);
        $this->assertDatabaseCount('official_source_refresh_events', 0);
    }

    public function test_unallowlisted_field_is_rejected(): void
    {
        $institution = Institution::create([
            'name' => 'Allowlist University',
            'slug' => 'allowlist-university',
            'status' => 'active',
        ]);
        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Campus',
            'slug' => 'campus',
            'status' => 'active',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not allowlisted');
        app(OfficialSourceCanonicalRefreshService::class)->refresh(
            $campus,
            'institution_id',
            $institution->id,
            999,
            $this->validEvidence()
        );
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
            'evidence_url' => 'https://university.example.edu/campuses',
            'evidence_excerpt' => 'The campus is now called Updated Campus Name.',
            'observed_at' => '2026-10-25T04:00:00Z',
            'content_fingerprint' => hash('sha256', 'official campus page'),
        ];
    }

    public function test_institution_refresh_fails_closed_without_persisted_field_approval_decision(): void
    {
        $institution = Institution::create([
            'name' => 'Institution Approval Gate University',
            'official_name' => 'Institution Approval Gate University',
            'short_name' => 'IAGU',
            'slug' => 'institution-approval-gate-university',
            'institution_type' => 'public university',
            'status' => 'active',
            'description' => 'Original official description.',
        ]);
        $campus = Campus::create([
            'institution_id' => $institution->id,
            'name' => 'Approval Gate Campus',
            'slug' => 'approval-gate-campus',
            'status' => 'active',
        ]);
        $before = $institution->fresh()->getAttributes();

        try {
            app(OfficialSourceCanonicalRefreshService::class)->refresh(
                $institution,
                'description',
                'Original official description.',
                'Updated official description.',
                array_merge($this->validEvidence(), [
                    'source_official' => true,
                    'source_identity_verified' => true,
                    'fetch_successful' => true,
                    'entity_match_confirmed' => true,
                    'source_id' => null,
                    'source_change_observation_id' => null,
                ])
            );
            $this->fail('Institution refresh must remain blocked until a persisted field-level approval is available.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('persisted field-level approval integration is unavailable', $exception->getMessage());
        }

        $this->assertSame($before, $institution->fresh()->getAttributes());
        $this->assertSame($institution->id, $campus->fresh()->institution_id);
        $this->assertDatabaseCount('official_source_refresh_events', 0);
    }

    public function test_institution_identity_fields_are_not_allowlisted_for_source_refresh(): void
    {
        $institution = Institution::create([
            'name' => 'Identity Guard University',
            'slug' => 'identity-guard-university',
            'status' => 'active',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not allowlisted');
        app(OfficialSourceCanonicalRefreshService::class)->refresh(
            $institution,
            'slug',
            'identity-guard-university',
            'different-university-slug',
            $this->validEvidence()
        );
    }

}
