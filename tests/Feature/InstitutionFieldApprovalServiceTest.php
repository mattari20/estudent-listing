<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Evidence;
use App\Models\Institution;
use App\Models\InstitutionFieldApproval;
use App\Models\OfficialSourceRefreshEvent;
use App\Models\Source;
use App\Models\SourceChangeObservation;
use App\Models\SourceSnapshot;
use App\Models\User;
use App\Services\InstitutionFieldApprovalService;
use App\Services\OfficialSourceCanonicalRefreshService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class InstitutionFieldApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $fixtureNumber = 0;

    public function test_operator_can_create_exact_pending_proposal_and_admin_can_approve_it(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
        $operator = $this->user('operator');
        $this->actingAs($operator);

        $proposal = app(InstitutionFieldApprovalService::class)->createPending(
            $institution->id, $source->id, $observation->id, $snapshot->id, $evidence->id,
            'Original description', 'Reviewed new description'
        );

        $this->assertSame('pending', $proposal->status);
        $this->assertSame('description', $proposal->field_name);
        $this->assertSame($institution->id, $proposal->institution_id);
        $this->assertSame($operator->id, $proposal->created_by);
        $this->assertSame($snapshot->content_hash, $proposal->content_fingerprint);

        $admin = $this->user('admin');
        $this->actingAs($admin);
        $approved = app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');

        $this->assertSame('approved', $approved->status);
        $this->assertSame($admin->id, $approved->reviewer_id);
        $this->assertNotNull($approved->decision_at);
        $this->assertSame('Original description', $approved->expected_current_value);
        $this->assertSame('Reviewed new description', $approved->proposed_value);
    }

    public function test_unauthorized_user_cannot_approve_a_pending_proposal(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
        $operator = $this->user('operator');
        $this->actingAs($operator);
        $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence);

        try {
            app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');
            $this->fail('A non-admin must not approve a proposal.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('admin is required', $exception->getMessage());
        }

        $this->assertSame('pending', $proposal->fresh()->status);
    }

    public function test_proposal_creator_cannot_approve_their_own_proposal(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
        $creator = $this->user('admin');
        $this->actingAs($creator);
        $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence);

        try {
            app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');
            $this->fail('Self-approval must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('cannot review their own', $exception->getMessage());
        }
    }

    public function test_pending_and_rejected_proposals_cannot_be_applied(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
        $operator = $this->user('operator');
        $this->actingAs($operator);
        $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence);
        $refresh = app(OfficialSourceCanonicalRefreshService::class);

        try {
            $refresh->refreshApprovedInstitutionDescription($proposal->id);
            $this->fail('Pending proposal must not be applied.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('approved, unapplied', $exception->getMessage());
        }

        $admin = $this->user('admin');
        $this->actingAs($admin);
        app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'rejected', 'Evidence does not support the wording.');
        try {
            $refresh->refreshApprovedInstitutionDescription($proposal->id);
            $this->fail('Rejected proposal must not be applied.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('approved, unapplied', $exception->getMessage());
        }

        $this->assertSame('Original description', $institution->fresh()->description);
        $this->assertDatabaseCount('official_source_refresh_events', 0);
    }

    public function test_approved_proposal_updates_description_and_audits_evidence_without_changing_identity_or_relationships(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence, $campus] = $this->fixture(true);
        $operator = $this->user('operator');
        $this->actingAs($operator);
        $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence);
        $admin = $this->user('admin');
        $this->actingAs($admin);
        app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');

        $result = app(OfficialSourceCanonicalRefreshService::class)->refreshApprovedInstitutionDescription($proposal->id);

        $this->assertSame('applied', $result['outcome']);
        $this->assertSame($institution->id, $result['institution']->id);
        $this->assertSame('Reviewed new description', $result['institution']->description);
        $this->assertSame($institution->id, $campus->fresh()->institution_id);
        $this->assertSame('applied', $result['approval']->status);
        $this->assertSame('applied', $result['approval']->application_outcome);
        $this->assertSame(1, $result['approval']->applied_refresh_event_id);

        $this->assertDatabaseCount('official_source_refresh_events', 1);
        $event = OfficialSourceRefreshEvent::query()->firstOrFail();
        $this->assertSame(Institution::class, $event->canonical_type);
        $this->assertSame($institution->id, $event->canonical_id);
        $this->assertSame('description', $event->field_name);
        $this->assertSame(['value' => 'Original description'], $event->before_value);
        $this->assertSame(['value' => 'Reviewed new description'], $event->after_value);
        $this->assertSame($evidence->reference, $event->evidence_url);
        $this->assertSame($evidence->excerpt, $event->evidence_excerpt);
        $this->assertSame($snapshot->content_hash, $event->content_fingerprint);
        $this->assertSame($observation->id, $event->source_change_observation_id);
    }

    public function test_stale_canonical_value_marks_proposal_terminal_without_a_refresh_event(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
        $this->actingAs($this->user('operator'));
        $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence);
        $this->actingAs($this->user('admin'));
        app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');
        $institution->description = 'Changed by another reviewer';
        $institution->save();

        $result = app(OfficialSourceCanonicalRefreshService::class)->refreshApprovedInstitutionDescription($proposal->id);

        $this->assertSame('stale', $result['outcome']);
        $this->assertSame('stale', $result['approval']->status);
        $this->assertSame('expected_value_changed', $result['approval']->application_outcome);
        $this->assertSame('Changed by another reviewer', $institution->fresh()->description);
        $this->assertDatabaseCount('official_source_refresh_events', 0);
    }

    public function test_changes_to_reviewed_proposal_identity_values_or_source_make_it_stale(): void
    {
        foreach (['expected_current_value', 'proposed_value', 'institution_id', 'source_id'] as $field) {
            [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
            $this->actingAs($this->user('operator'));
            $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence, 'Reviewed new description '.$field);
            $this->actingAs($this->user('admin'));
            app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');

            $changedValue = match ($field) {
                'expected_current_value' => 'tampered expected value',
                'proposed_value' => 'tampered proposed value',
                'institution_id' => Institution::create([
                    'name' => 'Second Institution '.$this->fixtureNumber,
                    'slug' => 'second-institution-'.$this->fixtureNumber,
                    'status' => 'active',
                    'description' => 'Second description',
                ])->id,
                'source_id' => Source::create([
                    'source_type' => 'official',
                    'name' => 'Other official source '.$this->fixtureNumber,
                    'url' => 'https://8.8.8.8/official',
                    'authority_scope' => 'official',
                    'status' => 'active',
                ])->id,
            };
            DB::table('institution_field_approvals')->where('id', $proposal->id)->update([$field => $changedValue]);

            $result = app(OfficialSourceCanonicalRefreshService::class)->refreshApprovedInstitutionDescription($proposal->id);
            $this->assertSame('stale', $result['outcome'], 'Mutation should invalidate '.$field);
            $this->assertSame('stale', $proposal->fresh()->status);
            $this->assertDatabaseCount('official_source_refresh_events', 0);
        }
    }

    public function test_changed_evidence_invalidates_approval(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
        $this->actingAs($this->user('operator'));
        $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence);
        $this->actingAs($this->user('admin'));
        app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');
        $evidence->excerpt = 'Changed after review';
        $evidence->save();

        $result = app(OfficialSourceCanonicalRefreshService::class)->refreshApprovedInstitutionDescription($proposal->id);

        $this->assertSame('stale', $result['outcome']);
        $this->assertSame('evidence_invalid', $result['approval']->application_outcome);
        $this->assertSame('Original description', $institution->fresh()->description);
        $this->assertDatabaseCount('official_source_refresh_events', 0);
    }

    public function test_unchanged_description_returns_no_change_and_creates_no_event(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
        $this->actingAs($this->user('operator'));
        $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence, 'Original description');
        $this->actingAs($this->user('admin'));
        app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');

        $result = app(OfficialSourceCanonicalRefreshService::class)->refreshApprovedInstitutionDescription($proposal->id);

        $this->assertSame('no_change', $result['outcome']);
        $this->assertSame('no_change', $result['approval']->application_outcome);
        $this->assertSame('Original description', $institution->fresh()->description);
        $this->assertDatabaseCount('official_source_refresh_events', 0);
    }

    public function test_replaying_an_applied_approval_is_rejected(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
        $this->actingAs($this->user('operator'));
        $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence);
        $this->actingAs($this->user('admin'));
        app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');
        $refresh = app(OfficialSourceCanonicalRefreshService::class);
        $refresh->refreshApprovedInstitutionDescription($proposal->id);

        try {
            $refresh->refreshApprovedInstitutionDescription($proposal->id);
            $this->fail('An applied approval must not be replayed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('approved, unapplied', $exception->getMessage());
        }

        $this->assertDatabaseCount('official_source_refresh_events', 1);
    }

    public function test_audit_failure_rolls_back_canonical_and_application_writes(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
        $this->actingAs($this->user('operator'));
        $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence);
        $this->actingAs($this->user('admin'));
        app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');

        OfficialSourceRefreshEvent::creating(function (): void {
            throw new RuntimeException('forced audit failure');
        });

        try {
            app(OfficialSourceCanonicalRefreshService::class)->refreshApprovedInstitutionDescription($proposal->id);
            $this->fail('The forced audit failure should abort the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced audit failure', $exception->getMessage());
        }

        $this->assertSame('Original description', $institution->fresh()->description);
        $this->assertSame('approved', $proposal->fresh()->status);
        $this->assertNull($proposal->fresh()->applied_at);
        $this->assertDatabaseCount('official_source_refresh_events', 0);
    }

    public function test_unauthenticated_review_is_rejected(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
        $this->actingAs($this->user('operator'));
        $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence);
        auth()->logout();

        try {
            app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');
            $this->fail('Unauthenticated review must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('authenticated institute admin', $exception->getMessage());
        }
        $this->assertSame('pending', $proposal->fresh()->status);
    }

    public function test_known_conflicting_verified_evidence_blocks_approval(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
        $conflictingEvidence = Evidence::create([
            'source_id' => $source->id,
            'snapshot_id' => $snapshot->id,
            'reference' => 'https://8.8.8.8/conflicting-page',
            'excerpt' => 'Conflicting official description.',
            'content_hash' => $snapshot->content_hash,
            'observed_at' => now(),
            'status' => 'active',
        ]);
        Assertion::create([
            'evidence_id' => $conflictingEvidence->id,
            'subject_type' => Institution::class,
            'subject_id' => $institution->id,
            'attribute' => 'description',
            'value' => 'A conflicting verified description',
            'verification_state' => 'verified',
        ]);

        $this->actingAs($this->user('operator'));
        $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence);
        $this->actingAs($this->user('admin'));
        try {
            app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');
            $this->fail('Conflicting verified evidence must block approval.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Conflicting active verified evidence', $exception->getMessage());
        }

        $this->assertSame('pending', $proposal->fresh()->status);
        $this->assertDatabaseCount('official_source_refresh_events', 0);
    }

    public function test_changed_snapshot_content_invalidates_approval(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
        $this->actingAs($this->user('operator'));
        $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence);
        $this->actingAs($this->user('admin'));
        app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');
        $snapshot->content = 'tampered captured source content';
        $snapshot->save();

        $result = app(OfficialSourceCanonicalRefreshService::class)->refreshApprovedInstitutionDescription($proposal->id);

        $this->assertSame('stale', $result['outcome']);
        $this->assertSame('evidence_invalid', $result['approval']->application_outcome);
        $this->assertSame('Original description', $institution->fresh()->description);
        $this->assertDatabaseCount('official_source_refresh_events', 0);
    }

    public function test_source_becoming_inactive_after_approval_invalidates_the_proposal(): void
    {
        [$institution, $source, $observation, $snapshot, $evidence] = $this->fixture();
        $this->actingAs($this->user('operator'));
        $proposal = $this->proposal($institution, $source, $observation, $snapshot, $evidence);
        $this->actingAs($this->user('admin'));
        app(InstitutionFieldApprovalService::class)->decide($proposal->id, 'approved');
        $source->status = 'inactive';
        $source->save();

        $result = app(OfficialSourceCanonicalRefreshService::class)->refreshApprovedInstitutionDescription($proposal->id);

        $this->assertSame('stale', $result['outcome']);
        $this->assertSame('evidence_invalid', $result['approval']->application_outcome);
        $this->assertSame('Original description', $institution->fresh()->description);
        $this->assertDatabaseCount('official_source_refresh_events', 0);
    }

    private function fixture(bool $withCampus = false): array
    {
        $this->fixtureNumber++;
        config(['institute_agent.profiles' => [
            'test-official' => ['source' => ['url' => 'https://8.8.8.8/official']],
        ]]);

        $institution = Institution::create([
            'name' => 'Approval Test University '.$this->fixtureNumber,
            'slug' => 'approval-test-university-'.$this->fixtureNumber,
            'status' => 'active',
            'description' => 'Original description',
        ]);
        $source = Source::create([
            'source_type' => 'official',
            'name' => 'Official University Source',
            'url' => 'https://8.8.8.8/official',
            'authority_scope' => 'official',
            'status' => 'active',
        ]);
        $snapshot = SourceSnapshot::create([
            'source_id' => $source->id,
            'reference' => 'https://8.8.8.8/official',
            'content_hash' => hash('sha256', 'Official source content '.$this->fixtureNumber),
            'content_length' => 120,
            'content_type' => 'text/html',
            'content' => 'Official source content '.$this->fixtureNumber,
            'captured_at' => now(),
            'status' => 'captured',
            'metadata' => [],
        ]);
        $observation = SourceChangeObservation::create([
            'source_id' => $source->id,
            'current_snapshot_id' => $snapshot->id,
            'change_state' => 'changed',
            'materiality' => 'material',
            'status' => 'detected',
            'summary' => 'Description changed',
            'details' => [],
            'observed_at' => now(),
        ]);
        $evidence = Evidence::create([
            'source_id' => $source->id,
            'snapshot_id' => $snapshot->id,
            'reference' => 'https://8.8.8.8/about',
            'excerpt' => 'The official institution description supports the reviewed change.',
            'content_hash' => $snapshot->content_hash,
            'observed_at' => now(),
            'status' => 'active',
        ]);
        $campus = null;
        if ($withCampus) {
            $campus = Campus::create([
                'institution_id' => $institution->id,
                'name' => 'Related Campus',
                'slug' => 'related-campus-'.$this->fixtureNumber,
                'status' => 'active',
            ]);
        }

        return [$institution, $source, $observation, $snapshot, $evidence, $campus];
    }

    private function proposal(Institution $institution, Source $source, SourceChangeObservation $observation, SourceSnapshot $snapshot, Evidence $evidence, ?string $proposed = 'Reviewed new description'): InstitutionFieldApproval
    {
        return app(InstitutionFieldApprovalService::class)->createPending(
            $institution->id, $source->id, $observation->id, $snapshot->id, $evidence->id,
            $institution->description, $proposed
        );
    }

    private function user(string $role): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'name' => ucfirst($role).' Reviewer '.$number,
            'email' => $role.'-'.$number.'@example.test',
            'password' => 'temporary-password',
            'role' => $role,
        ]);
    }
}
