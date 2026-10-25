<?php

namespace App\Services;

use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Models\SourceSnapshot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class InstituteDiscoveryAgentService
{
    public function run(string $profile = 'cui-lahore', bool $useAi = false): array
    {
        $definition = $this->definition($profile);
        $institution = $this->institution($definition);

        $sourceDefinition = $definition['source'];
        $source = Source::query()->updateOrCreate(
            ['url' => $sourceDefinition['url']],
            [
                'source_type' => $sourceDefinition['type'],
                'name' => $sourceDefinition['name'],
                'authority_scope' => $sourceDefinition['authority_scope'],
                'status' => 'active',
            ]
        );

        app(DiscoveryMonitoringPolicyService::class)->ensure($source, $profile);

        $response = app(SafeSourceHttpRetriever::class)->get(
            $source->url,
            30,
            10,
            'eStudent-InstituteDiscoveryAgent/1.0'
        );

        if (! $response->successful()) {
            throw new \RuntimeException('Discovery source returned HTTP '.$response->status().'.');
        }

        $body = $response->body();

        if (strlen($body) > 5 * 1024 * 1024) {
            throw new \RuntimeException('Discovery source exceeds the 5 MB retrieval limit.');
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
                    'extraction_method' => $useAi ? 'ai_extraction' : 'deterministic',
                ]
            );
        }

        return $this->persistCandidates($profile, $institution, $source, $snapshot, $useAi);
    }

    public function runFromSnapshot(
        string $profile,
        SourceSnapshot $snapshot,
        bool $useAi = false,
    ): array {
        $definition = $this->definition($profile);
        $institution = $this->institution($definition);
        $source = Source::query()->findOrFail($snapshot->source_id);

        if ($source->url !== ($definition['source']['url'] ?? null)) {
            throw new \RuntimeException('The monitoring snapshot does not belong to the configured discovery profile.');
        }

        return $this->persistCandidates($profile, $institution, $source, $snapshot, $useAi);
    }

    private function persistCandidates(
        string $profile,
        Institution $institution,
        Source $source,
        SourceSnapshot $snapshot,
        bool $useAi
    ): array {
        if (! is_string($snapshot->content) || $snapshot->content === '') {
            throw new \RuntimeException('The discovery snapshot has no stored source content.');
        }

        $entityType = $this->entityType($profile);

        if ($entityType !== 'program' && $useAi) {
            throw new \RuntimeException('AI extraction is currently enabled only for programme discovery profiles.');
        }

        $existingBatch = $this->existingBatchForSnapshot($profile, $source, $snapshot, $useAi);
        if ($existingBatch) {
            return $this->summaryForExistingBatch(
                $existingBatch,
                $institution,
                $source,
                $snapshot,
                $useAi
            );
        }

        $candidates = $entityType === 'program' && $useAi
            ? $this->extractWithAi($snapshot->content, $institution, $source->url)
            : app(InstituteDiscoveryCandidateExtractor::class)->extract(
                $entityType,
                $snapshot->content
            );

        if ($candidates === []) {
            return $this->persistEmptyOutcome($profile, $institution, $source, $snapshot, $entityType, $useAi);
        }

        $excerptLines = [];
        foreach ($candidates as $program) {
            $excerptLines[] = $program['name']
                .(! empty($program['evidence_excerpt']) ? ' — '.$program['evidence_excerpt'] : '');
        }

        $excerpt = ($useAi ? 'AI extraction' : 'Discovery agent')
            .' extracted '.count($candidates).' '.$entityType.' candidates from '
            .$source->url."\n\n".implode("\n", $excerptLines);

        $evidence = Evidence::create([
            'source_id' => $source->id,
            'snapshot_id' => $snapshot->id,
            'reference' => $source->url,
            'excerpt' => mb_substr($excerpt, 0, 10000),
            'content_hash' => $snapshot->content_hash,
            'observed_at' => now(),
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => ($useAi ? 'ai-' : 'agent-').$profile.'-'.Str::lower(Str::random(16)),
            'entity_type' => $entityType,
            'source_type' => $useAi ? 'ai_extraction' : 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'received_at' => now(),
            'notes' => 'Discovery candidates from source snapshot #'.$snapshot->id
                .'. discovery_extractor_version='.InstituteDiscoveryCandidateExtractor::VERSION
                .'. Candidates remain non-canonical until governed approval and promotion.',
        ]);

        $candidateRows = 0;
        $duplicateRows = 0;
        $invalidRows = 0;
        $seenSlugs = [];

        foreach ($candidates as $index => $candidate) {
            $payload = $this->candidatePayload($entityType, $candidate, $useAi);
            $payload['_provenance']['source_id'] = $source->id;
            $payload['_provenance']['snapshot_id'] = $snapshot->id;
            $payload['_provenance']['evidence_id'] = $evidence->id;
            $payload['_provenance']['source_url'] = $source->url;
            $payload['_provenance']['source_authority'] = $source->authority_scope;
            $payload['_provenance']['captured_at'] = $snapshot->captured_at?->toIso8601String();
            $payload['_provenance']['content_hash'] = $snapshot->content_hash;
            $payload['_provenance']['extractor_version'] = InstituteDiscoveryCandidateExtractor::VERSION;

            $blockers = app(InstituteDiscoveryCandidateQualityService::class)->blockers(
                $entityType,
                $candidate,
                $source->url,
                $useAi
            );

            $slug = (string) ($payload['slug'] ?? '');
            $withinBatchDuplicate = $slug !== '' && isset($seenSlugs[$slug]);
            if ($slug !== '') {
                $seenSlugs[$slug] = true;
            }

            $duplicate = null;
            if ($blockers === [] && $withinBatchDuplicate) {
                $duplicate = [
                    'kind' => 'same_batch',
                    'message' => 'A candidate with the same normalized slug already appears earlier in this source batch.',
                ];
            } elseif ($blockers === []) {
                $duplicate = app(ImportDuplicateDetectorService::class)->find(
                    $entityType,
                    $institution->id,
                    $slug,
                    $batch->id
                );
            }

            $status = $blockers !== [] ? 'invalid' : ($duplicate ? 'duplicate' : 'candidate');
            $validationErrors = $blockers !== []
                ? $blockers
                : ($duplicate ? [$duplicate['message']] : null);

            ImportRow::create([
                'import_batch_id' => $batch->id,
                'row_number' => $index + 1,
                'status' => $status,
                'match_state' => $blockers !== [] ? 'unresolved' : ($duplicate ? 'duplicate' : 'unresolved'),
                'parent_subject_type' => Institution::class,
                'parent_subject_id' => $institution->id,
                'payload' => $payload,
                'normalized_payload' => $payload,
                'validation_errors' => $validationErrors,
                'notes' => 'Evidence #'.$evidence->id.' from '.$source->url,
            ]);

            Assertion::create([
                'evidence_id' => $evidence->id,
                'subject_type' => Institution::class,
                'subject_id' => $institution->id,
                'attribute' => 'candidate_'.$entityType,
                'value' => (string) ($candidate['name'] ?? ''),
                'verification_state' => $useAi ? 'ai_extracted' : 'discovered',
                'confidence' => is_numeric($candidate['confidence'] ?? null)
                    ? (float) $candidate['confidence']
                    : ($entityType === 'campus' ? 0.9000 : 0.9500),
            ]);

            if ($status === 'candidate') {
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
            'completed_at' => now(),
        ]);

        $reviewPackage = app(InstituteDiscoveryReviewPackageService::class)->create($batch->fresh(), $snapshot, (int) $evidence->id);

        return [
            'entity_type' => $entityType,
            'profile' => $profile,
            'institution_id' => $institution->id,
            'source_id' => $source->id,
            'snapshot_id' => $snapshot->id,
            'evidence_id' => $evidence->id,
            'batch_id' => $batch->id,
            'total_rows' => count($candidates),
            'candidate_rows' => $candidateRows,
            'duplicate_rows' => $duplicateRows,
            'extraction_method' => $useAi ? 'ai_extraction' : 'deterministic',
            'review_package' => $reviewPackage,
        ];
    }

    /**
     * Return the immutable batch already produced for this profile, source content,
     * extraction method, and extractor version. Old batches remain untouched.
     */
    private function existingBatchForSnapshot(
        string $profile,
        Source $source,
        SourceSnapshot $snapshot,
        bool $useAi,
    ): ?ImportBatch {
        $sourceType = $useAi ? 'ai_extraction' : 'automated_discovery';
        $batchPrefix = ($useAi ? 'ai-' : 'agent-').$profile.'-';
        $versionMarker = 'discovery_extractor_version='.InstituteDiscoveryCandidateExtractor::VERSION;

        $batches = ImportBatch::query()
            ->where('source_id', $source->id)
            ->where('source_type', $sourceType)
            ->where('batch_key', 'like', $batchPrefix.'%')
            ->where('notes', 'like', '%'.$versionMarker.'%')
            ->latest('id')
            ->get();

        foreach ($batches as $batch) {
            if (preg_match('/source snapshot #(\\d+)/i', (string) $batch->notes, $matches) !== 1) {
                continue;
            }

            $previousSnapshot = SourceSnapshot::query()->find((int) $matches[1]);
            if ($previousSnapshot && hash_equals((string) $previousSnapshot->content_hash, (string) $snapshot->content_hash)) {
                return $batch;
            }
        }

        return null;
    }

    private function summaryForExistingBatch(
        ImportBatch $batch,
        Institution $institution,
        Source $source,
        SourceSnapshot $snapshot,
        bool $useAi,
    ): array {
        $batch->loadMissing('rows');
        $batchSnapshotId = $snapshot->id;
        if (preg_match('/source snapshot #(\\d+)/i', (string) $batch->notes, $matches) === 1) {
            $batchSnapshotId = (int) $matches[1];
        }
        $batchSnapshot = SourceSnapshot::query()->find($batchSnapshotId) ?? $snapshot;
        $evidenceId = Evidence::query()
            ->where('source_id', $source->id)
            ->where('snapshot_id', $batchSnapshot->id)
            ->latest('id')
            ->value('id');
        $reviewPackage = app(InstituteDiscoveryReviewPackageService::class)->create(
            $batch,
            $batchSnapshot,
            $evidenceId === null ? null : (int) $evidenceId
        );

        $totalRows = (int) ($batch->total_rows ?? $batch->rows->count());
        $candidateRows = $batch->rows->where('status', 'candidate')->count();
        $duplicateRows = $batch->rows->where('status', 'duplicate')->count();

        return [
            'entity_type' => $batch->entity_type,
            'profile' => $institution->slug,
            'institution_id' => $institution->id,
            'source_id' => $source->id,
            'snapshot_id' => $batchSnapshot->id,
            'evidence_id' => $evidenceId === null ? null : (int) $evidenceId,
            'batch_id' => $batch->id,
            'total_rows' => $totalRows,
            'candidate_rows' => $candidateRows,
            'duplicate_rows' => $duplicateRows,
            'extraction_method' => $useAi ? 'ai_extraction' : 'deterministic',
            'outcome' => $totalRows === 0 ? 'no_candidates' : 'reused_existing_batch',
            'review_package' => $reviewPackage,
        ];
    }

    private function persistEmptyOutcome(
        string $profile,
        Institution $institution,
        Source $source,
        SourceSnapshot $snapshot,
        string $entityType,
        bool $useAi
    ): array {
        $evidence = Evidence::create([
            'source_id' => $source->id,
            'snapshot_id' => $snapshot->id,
            'reference' => $source->url,
            'excerpt' => 'No '.$entityType.' candidates were extracted from this source snapshot.',
            'content_hash' => $snapshot->content_hash,
            'observed_at' => now(),
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => ($useAi ? 'ai-' : 'agent-').$profile.'-'.Str::lower(Str::random(16)),
            'entity_type' => $entityType,
            'source_type' => $useAi ? 'ai_extraction' : 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'candidate_rows' => 0,
            'received_at' => now(),
            'completed_at' => now(),
            'notes' => 'No candidates extracted from source snapshot #'.$snapshot->id
                .'. discovery_extractor_version='.InstituteDiscoveryCandidateExtractor::VERSION
                .'. This is a recorded no-change outcome; no canonical data was changed.',
        ]);

        $reviewPackage = app(InstituteDiscoveryReviewPackageService::class)
            ->create($batch->fresh(), $snapshot, (int) $evidence->id);

        return [
            'entity_type' => $entityType,
            'profile' => $profile,
            'institution_id' => $institution->id,
            'source_id' => $source->id,
            'snapshot_id' => $snapshot->id,
            'evidence_id' => $evidence->id,
            'batch_id' => $batch->id,
            'total_rows' => 0,
            'candidate_rows' => 0,
            'duplicate_rows' => 0,
            'extraction_method' => $useAi ? 'ai_extraction' : 'deterministic',
            'outcome' => 'no_candidates',
            'review_package' => $reviewPackage,
        ];
    }

    private function entityType(string $profile): string
    {
        return (string) (config("institute_agent.profiles.{$profile}.entity_type") ?: 'program');
    }

    private function candidatePayload(string $entityType, array $candidate, bool $useAi): array
    {
        if ($entityType === 'campus') {
            return [
                'name' => $candidate['name'],
                'slug' => Str::slug($candidate['name']),
                'campus_type' => $candidate['campus_type'] ?? 'campus',
                'status' => $candidate['status'] ?? 'unknown',
                '_provenance' => [
                    'method' => $useAi ? 'ai_extraction' : 'deterministic',
                    'confidence' => $candidate['confidence'] ?? 0.9000,
                    'evidence_excerpt' => $candidate['evidence_excerpt'] ?? null,
                ],
            ];
        }

        return [
            'name' => $candidate['name'],
            'slug' => Str::slug($candidate['name']),
            'program_level' => $candidate['program_level'] ?? 'undergraduate',
            'award' => $candidate['award'] ?? null,
            'discipline' => $candidate['discipline'] ?? null,
            'status' => $candidate['status'] ?? 'unknown',
            'description' => $useAi
                ? 'AI-extracted candidate from the official institutional programme source.'
                : 'Discovered from the official institutional programme source.',
            '_provenance' => [
                'method' => $useAi ? 'ai_extraction' : 'deterministic',
                'confidence' => $candidate['confidence'] ?? 0.9500,
                'evidence_excerpt' => $candidate['evidence_excerpt'] ?? null,
            ],
        ];
    }

    private function definition(string $profile): array
    {
        $definition = config("institute_agent.profiles.{$profile}");

        if (! is_array($definition)) {
            throw new \InvalidArgumentException("Unknown institute discovery profile: {$profile}");
        }

        return $definition;
    }

    private function institution(array $definition): Institution
    {
        $institution = Institution::query()
            ->where('slug', $definition['institution_slug'])
            ->first();

        if (! $institution) {
            throw new \RuntimeException(
                'The discovery profile requires an existing parent institution: '
                .$definition['institution_slug']
            );
        }

        if (($definition['entity_type'] ?? null) === 'campus') {
            $identity = Str::lower(implode(' ', array_filter([
                $institution->name,
                $institution->official_name,
                $institution->short_name,
                $institution->institution_type,
                $institution->slug,
            ])));

            if (preg_match('/(^|[^a-z])campus([^a-z]|$)/', $identity) === 1) {
                throw new \RuntimeException(
                    'Campus discovery requires a standalone parent institution. The configured parent appears to be campus-level; resolve the canonical parent institution before running campus discovery.'
                );
            }
        }

        return $institution;
    }

    private function extractWithAi(string $html, Institution $institution, string $sourceUrl): array
    {
        try {
            return app(InstituteAiExtractionService::class)->extract($html, [
                'institution_name' => $institution->name,
                'institution_slug' => $institution->slug,
                'source_url' => $sourceUrl,
                'entity_type' => 'program',
            ]);
        } catch (\Throwable $e) {
            if (config('institute_ai.fallback_to_deterministic', false)) {
                return $this->extractBachelorPrograms($html);
            }

            throw $e;
        }
    }

}