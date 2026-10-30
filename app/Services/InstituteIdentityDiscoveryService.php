<?php

namespace App\Services;

use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Source;
use App\Models\SourceSnapshot;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stages top-level institution identities from a configured official source.
 *
 * This deliberately creates evidence and a private review batch only. It never
 * creates, updates, approves, promotes, or publishes a canonical institution.
 */
class InstituteIdentityDiscoveryService
{
    public const VERSION = '1';

    public function run(string $profile, Source $source, array $definition): array
    {
        if (($definition['entity_type'] ?? null) !== 'institution') {
            throw new RuntimeException('Institution identity discovery requires entity_type=institution.');
        }

        if (($definition['monitoring']['use_ai'] ?? false) === true) {
            throw new RuntimeException('Institution identity discovery is deterministic-only; AI extraction is not permitted.');
        }

        $response = app(SafeSourceHttpRetriever::class)->get(
            $source->url,
            30,
            10,
            'eStudent-InstitutionIdentityDiscovery/1.0'
        );

        if (! $response->successful()) {
            throw new RuntimeException('Official institution identity source returned HTTP '.$response->status().'.');
        }

        $body = $response->body();
        if (strlen($body) > 5 * 1024 * 1024) {
            throw new RuntimeException('Institution identity source exceeds the 5 MB retrieval limit.');
        }

        $contentHash = hash('sha256', $body);
        $snapshot = SourceSnapshot::query()
            ->where('source_id', $source->id)
            ->where('content_hash', $contentHash)
            ->latest('captured_at')
            ->first();

        if (! $snapshot) {
            $snapshot = app(SourceSnapshotService::class)->capture(
                $source,
                $body,
                $source->url,
                $response->header('Content-Type'),
                [
                    'agent_profile' => $profile,
                    'retrieval_status' => $response->status(),
                    'extraction_method' => 'deterministic_institution_identity',
                ]
            );
        }

        return $this->stageSnapshot($profile, $source, $snapshot, $definition);
    }

    public function runFromSnapshot(string $profile, SourceSnapshot $snapshot, array $definition): array
    {
        if (($definition['entity_type'] ?? null) !== 'institution') {
            throw new RuntimeException('Institution identity discovery requires entity_type=institution.');
        }

        $source = Source::query()->findOrFail($snapshot->source_id);
        if ($source->url !== ($definition['source']['url'] ?? null)) {
            throw new RuntimeException('The monitoring snapshot does not belong to the configured institution identity profile.');
        }

        if (($definition['monitoring']['use_ai'] ?? false) === true) {
            throw new RuntimeException('Institution identity discovery is deterministic-only; AI extraction is not permitted.');
        }

        return $this->stageSnapshot($profile, $source, $snapshot, $definition);
    }

    private function stageSnapshot(string $profile, Source $source, SourceSnapshot $snapshot, array $definition): array
    {
        // Serialize identity candidate persistence per source, matching the generic
        // discovery agent's idempotency boundary. Concurrent scheduled/manual runs
        // must not both observe an absent batch and create parallel identity rows.
        return \Illuminate\\Support\\Facades\\DB::transaction(function () use ($profile, $source, $snapshot, $definition): array {
            $lockedSource = Source::query()->whereKey($source->id)->lockForUpdate()->firstOrFail();

            return $this->stageSnapshotLocked($profile, $lockedSource, $snapshot, $definition);
        });
    }

    private function stageSnapshotLocked(string $profile, Source $source, SourceSnapshot $snapshot, array $definition): array
    {
        $versionMarker = 'institution_identity_extractor_version='.self::VERSION;
        $existingBatches = ImportBatch::query()
            ->where('source_id', $source->id)
            ->where('entity_type', 'institution')
            ->where('source_type', 'automated_discovery')
            ->where('batch_key', 'like', 'agent-'.$profile.'-%')
            ->where('notes', 'like', '%'.$versionMarker.'%')
            ->latest('id')
            ->get();

        foreach ($existingBatches as $existingBatch) {
            if (preg_match('/source snapshot #(\d+)/i', (string) $existingBatch->notes, $matches) !== 1) {
                continue;
            }

            $previousSnapshot = SourceSnapshot::query()->find((int) $matches[1]);
            if ($previousSnapshot && hash_equals((string) $previousSnapshot->content_hash, (string) $snapshot->content_hash)) {
                $evidenceId = Evidence::query()
                    ->where('source_id', $source->id)
                    ->where('snapshot_id', $previousSnapshot->id)
                    ->latest('id')
                    ->value('id');
                $package = app(InstituteDiscoveryReviewPackageService::class)->create(
                    $existingBatch,
                    $previousSnapshot,
                    $evidenceId === null ? null : (int) $evidenceId
                );

                return [
                    'entity_type' => 'institution',
                    'profile' => $profile,
                    'institution_id' => null,
                    'source_id' => $source->id,
                    'snapshot_id' => $previousSnapshot->id,
                    'evidence_id' => $evidenceId === null ? null : (int) $evidenceId,
                    'batch_id' => $existingBatch->id,
                    'total_rows' => (int) ($existingBatch->total_rows ?? $existingBatch->rows()->count()),
                    'candidate_rows' => $existingBatch->rows()->where('status', 'candidate')->count(),
                    'duplicate_rows' => $existingBatch->rows()->where('status', 'duplicate')->count(),
                    'outcome' => 'reused_existing_batch',
                    'review_package' => $package,
                    'canonical_mutations_performed' => false,
                ];
            }
        }

        $identity = $definition['institution_identity'] ?? [];
        $candidates = app(InstituteDiscoveryCandidateExtractor::class)->extract('institution', $snapshot->content, $identity);

        $evidenceExcerpt = $candidates !== []
            ? 'Official institution identity corroborated by: '.($candidates[0]['evidence_excerpt'] ?? $candidates[0]['name'])
            : 'Configured institution identity terms were not found in this official source snapshot; no identity candidate was staged.';

        $evidence = Evidence::create([
            'source_id' => $source->id,
            'snapshot_id' => $snapshot->id,
            'reference' => $source->url,
            'excerpt' => mb_substr($evidenceExcerpt, 0, 10000),
            'content_hash' => $snapshot->content_hash,
            'observed_at' => now(),
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => 'agent-'.$profile.'-'.Str::lower(Str::random(16)),
            'entity_type' => 'institution',
            'source_type' => 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'received_at' => now(),
            'notes' => 'Official institution identity candidates from source snapshot #'.$snapshot->id
                .'. discovery_extractor_version='.InstituteDiscoveryCandidateExtractor::VERSION
                .'. '.$versionMarker
                .'. Rows remain non-canonical until identity resolution, explicit operator approval, and the separate publication workflow.',
        ]);

        $candidateRows = 0;
        $duplicateRows = 0;
        $invalidRows = 0;
        $seenSlugs = [];

        foreach ($candidates as $index => $candidate) {
            $payload = [
                'name' => $candidate['name'],
                'official_name' => $candidate['official_name'],
                'short_name' => $candidate['short_name'],
                'slug' => $candidate['slug'],
                'institution_type' => $candidate['institution_type'],
                'status' => 'candidate',
                '_provenance' => [
                    'method' => 'deterministic_institution_identity',
                    'confidence' => $candidate['confidence'] ?? 0.98,
                    'evidence_excerpt' => $candidate['evidence_excerpt'] ?? null,
                    'source_id' => $source->id,
                    'snapshot_id' => $snapshot->id,
                    'evidence_id' => $evidence->id,
                    'source_url' => $source->url,
                    'source_authority' => $source->authority_scope,
                    'captured_at' => $snapshot->captured_at?->toIso8601String(),
                    'content_hash' => $snapshot->content_hash,
                    'extractor_version' => self::VERSION,
                ],
            ];

            $blockers = app(InstituteDiscoveryCandidateQualityService::class)->blockers(
                'institution',
                $candidate,
                $source->url,
                false
            );
            $slug = (string) ($payload['slug'] ?? '');
            $withinBatchDuplicate = $slug !== '' && isset($seenSlugs[$slug]);
            if ($slug !== '') {
                $seenSlugs[$slug] = true;
            }
            if ($withinBatchDuplicate) {
                $blockers[] = 'duplicate_identity_slug_within_batch';
            }

            $status = $blockers === []
                ? 'candidate'
                : ($withinBatchDuplicate && count($blockers) === 1 && $blockers[0] === 'duplicate_identity_slug_within_batch'
                    ? 'duplicate'
                    : 'invalid');
            $row = ImportRow::create([
                'import_batch_id' => $batch->id,
                'row_number' => $index + 1,
                'status' => $status,
                'match_state' => $status === 'duplicate' ? 'duplicate' : 'unresolved',
                'parent_subject_type' => null,
                'parent_subject_id' => null,
                'payload' => $payload,
                'normalized_payload' => $payload,
                'validation_errors' => $blockers === [] ? null : array_values(array_unique($blockers)),
                'notes' => 'Official identity evidence #'.$evidence->id.' from '.$source->url,
            ]);

            Assertion::create([
                'evidence_id' => $evidence->id,
                'subject_type' => ImportRow::class,
                'subject_id' => $row->id,
                'attribute' => 'institution_identity_candidate',
                'value' => $candidate['name'],
                'verification_state' => 'discovered',
                'confidence' => (float) ($candidate['confidence'] ?? 0.98),
            ]);

            if ($status === 'candidate') {
                // This creates identity-match candidates and conflict records only.
                // It never approves or promotes the identity.
                app(InstituteIdentityResolver::class)->resolve($row);
                $candidateRows++;
            } elseif ($status === 'duplicate') {
                $duplicateRows++;
            } else {
                $invalidRows++;
            }
        }

        $batch->update([
            'total_rows' => count($candidates),
            'valid_rows' => $candidateRows + $duplicateRows,
            'invalid_rows' => $invalidRows,
            'candidate_rows' => $candidateRows,
            'duplicate_rows' => $duplicateRows,
            'completed_at' => now(),
        ]);

        $package = app(InstituteDiscoveryReviewPackageService::class)->create(
            $batch->fresh(),
            $snapshot,
            (int) $evidence->id
        );

        return [
            'entity_type' => 'institution',
            'profile' => $profile,
            'institution_id' => null,
            'source_id' => $source->id,
            'snapshot_id' => $snapshot->id,
            'evidence_id' => $evidence->id,
            'batch_id' => $batch->id,
            'total_rows' => count($candidates),
            'candidate_rows' => $candidateRows,
            'duplicate_rows' => $duplicateRows,
            'invalid_rows' => $invalidRows,
            'outcome' => $candidates === [] ? 'no_candidates' : 'review_required',
            'review_package' => $package,
            'canonical_mutations_performed' => false,
            'automatic_approval_performed' => false,
        ];
    }
}
