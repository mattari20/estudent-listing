<?php

namespace App\Console\Commands;

use App\Services\InstituteDiscoveryCandidateExtractor;
use App\Services\SafeSourceHttpRetriever;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class QualifyOfficialInstituteSources extends Command
{
    protected $signature = 'institutes:qualify-official-sources
        {--stage=hec-universities : hec-universities, hec-campuses, or provincial}
        {--timeout=15 : Per-source total timeout in seconds}
        {--connect-timeout=5 : Per-source connection timeout in seconds}
        {--delay-ms=1000 : Delay between independent sources}
        {--output= : Optional private report filename}';

    protected $description = 'Read-only live qualification of configured official institute sources; never persists discovery data.';

    public function handle(SafeSourceHttpRetriever $retriever, InstituteDiscoveryCandidateExtractor $extractor): int
    {
        $stage = (string) $this->option('stage');
        $inventory = config('institute_source_qualification.'.$stage);
        if (! is_array($inventory) || $inventory === []) {
            $this->error('Unknown or empty qualification stage: '.$stage);
            return self::INVALID;
        }

        $timeout = max(3, min(30, (int) $this->option('timeout')));
        $connectTimeout = max(2, min(10, (int) $this->option('connect-timeout')));
        $delayMs = max(0, min(10000, (int) $this->option('delay-ms')));
        $results = [];
        $started = microtime(true);

        foreach ($inventory as $definition) {
            $profile = isset($definition['profile'])
                ? config('institute_agent.profiles.'.$definition['profile'])
                : null;
            $url = $profile['source']['url'] ?? $definition['url'] ?? null;
            $identity = $profile['institution_identity'] ?? [];
            $sourceStarted = microtime(true);
            $result = [
                'source_id' => $definition['id'],
                'authority_scope' => $definition['authority'],
                'configured_url' => $url,
                'qualification_level' => 'AUTHORITY_LEAD',
                'retrieved_at' => now()->toIso8601String(),
                'duration_ms' => null,
                'http_status' => null,
                'content_type' => null,
                'final_url' => null,
                'raw_sha256' => null,
                'response_bytes' => null,
                'extraction_method' => null,
                'extracted_count' => 0,
                'institutions' => [],
                'identity_conflicts' => [],
                'campus_parent_status' => 'not_applicable',
                'identity_validation_status' => 'NOT_RUN_NO_CANONICAL_OR_STAGED_FIXTURE_INPUT',
                'error' => null,
            ];

            try {
                if (! is_string($url) || $url === '') {
                    throw new \RuntimeException('Configured source URL is missing.');
                }
                $retrieval = $retriever->getWithMetadata(
                    $url,
                    $timeout,
                    $connectTimeout,
                    'eStudent-OfficialSourceQualification/1.0'
                );
                $response = $retrieval['response'];
                $body = $response->body();
                $result['retrieved_at'] = now()->toIso8601String();
                $result['http_status'] = $response->status();
                $result['content_type'] = $response->header('Content-Type');
                $result['final_url'] = $retrieval['final_url'];
                $result['raw_sha256'] = hash('sha256', $body);
                $result['response_bytes'] = strlen($body);

                if (! $response->successful()) {
                    $result['qualification_level'] = 'BLOCKED';
                    throw new \RuntimeException('HTTP '.$response->status());
                }
                if (strlen($body) > 5 * 1024 * 1024) {
                    $result['qualification_level'] = 'BLOCKED';
                    throw new \RuntimeException('Response exceeds the 5 MiB qualification limit.');
                }
                if (! is_string($result['content_type']) || ! str_contains(strtolower($result['content_type']), 'text/html')) {
                    $result['qualification_level'] = 'BLOCKED';
                    throw new \RuntimeException('Expected an HTML content type; response was not parsed.');
                }

                $result['qualification_level'] = 'LIVE_RETRIEVED';
                $extractorType = $definition['extractor'] ?? (is_array($identity) && $identity !== [] ? 'institution' : 'unsupported-directory');
                $result['extraction_method'] = $extractorType.'; extractor_version='.InstituteDiscoveryCandidateExtractor::VERSION;

                if ($extractorType === 'unsupported-directory') {
                    $result['qualification_level'] = 'BLOCKED';
                    $result['error'] = 'Live response captured, but no deterministic directory-specific extractor is configured; no generic institution assumptions were made.';
                } else {
                    $candidates = $extractor->extract($extractorType, $body, $identity);
                    $result['institutions'] = array_map(static fn (array $candidate): array => [
                        'name' => $candidate['name'] ?? null,
                        'official_name' => $candidate['official_name'] ?? null,
                        'short_name' => $candidate['short_name'] ?? null,
                        'slug' => $candidate['slug'] ?? null,
                        'evidence_excerpt' => $candidate['evidence_excerpt'] ?? null,
                    ], $candidates);
                    $result['extracted_count'] = count($candidates);
                    $result['qualification_level'] = $candidates === [] ? 'BLOCKED' : 'EXTRACTION_QUALIFIED';
                    if ($extractorType === 'campus') {
                        $result['campus_parent_status'] = 'HUMAN_REVIEW_REQUIRED';
                        $result['identity_conflicts'][] = 'Campus extractor returns campus labels only; parent-university relationships are not asserted or promoted.';
                        $result['qualification_level'] = 'BLOCKED';
                    }
                    // Extraction alone cannot establish identity qualification. That
                    // requires a separate canonical/staged identity cross-check.
                }
            } catch (\Throwable $exception) {
                $result['error'] = $result['error'] ?: $exception->getMessage();
                if ($result['qualification_level'] === 'AUTHORITY_LEAD') {
                    $result['qualification_level'] = 'BLOCKED';
                }
            } finally {
                $result['duration_ms'] = (int) round((microtime(true) - $sourceStarted) * 1000);
            }

            $results[] = $result;
            $this->line(($result['qualification_level']).' '.$result['source_id'].' — '.($result['http_status'] ?? 'no HTTP response').' — '.($result['error'] ?? ($result['extracted_count'].' extracted')));
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        $report = [
            'schema_version' => 1,
            'mode' => 'read_only_in_memory_qualification',
            'stage' => $stage,
            'generated_at' => now()->toIso8601String(),
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'source_count' => count($results),
            'live_retrieval_successes' => count(array_filter($results, static fn (array $r): bool => $r['http_status'] !== null && $r['http_status'] >= 200 && $r['http_status'] < 300)),
            'extraction_successes' => count(array_filter($results, static fn (array $r): bool => $r['extracted_count'] > 0)),
            'identity_qualified_sources' => count(array_filter($results, static fn (array $r): bool => $r['qualification_level'] === 'IDENTITY_QUALIFIED')),
            'blocked_sources' => count(array_filter($results, static fn (array $r): bool => $r['qualification_level'] === 'BLOCKED')),
            'database_writes_performed' => false,
            'discovery_or_canonical_mutations_performed' => false,
            'results' => $results,
        ];
        $filename = $this->option('output') ?: 'source-qualification/'.$stage.'-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put('private/'.$filename, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['sources', $report['source_count']],
            ['live retrieval successes', $report['live_retrieval_successes']],
            ['extraction successes', $report['extraction_successes']],
            ['identity-qualified sources', $report['identity_qualified_sources']],
            ['blocked sources', $report['blocked_sources']],
        ]);
        $this->info('Private report written to storage/app/private/'.$filename);
        $this->comment('Read-only guarantee: this command does not access Eloquent models or database services.');
        return self::SUCCESS;
    }
}
