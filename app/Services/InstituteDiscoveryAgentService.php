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
        $definition = config("institute_agent.profiles.{$profile}");

        if (!is_array($definition)) {
            throw new \InvalidArgumentException("Unknown institute discovery profile: {$profile}");
        }

        $institution = Institution::query()
            ->where('slug', $definition['institution_slug'])
            ->first();

        if (!$institution) {
            throw new \RuntimeException(
                'The discovery profile requires an existing parent institution: '
                .$definition['institution_slug']
            );
        }

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

        app(SourceUrlGuard::class)->validate($source->url);

        $response = Http::timeout(30)
            ->connectTimeout(10)
            ->withHeaders(['User-Agent' => 'eStudent-InstituteDiscoveryAgent/1.0'])
            ->get($source->url);

        if (!$response->successful()) {
            throw new \RuntimeException('Discovery source returned HTTP '.$response->status().'.');
        }

        $body = $response->body();
        if (strlen($body) > 5 * 1024 * 1024) {
            throw new \RuntimeException('Discovery source exceeds the 5 MB retrieval limit.');
        }

        $snapshot = SourceSnapshot::create([
            'source_id' => $source->id,
            'reference' => $source->url,
            'content_hash' => hash('sha256', $body),
            'content_length' => strlen($body),
            'content_type' => $response->header('Content-Type'),
            'content' => $body,
            'captured_at' => now(),
            'status' => 'captured',
            'metadata' => [
                'agent_profile' => $profile,
                'retrieval_status' => $response->status(),
                'extraction_method' => $useAi ? 'ai_extraction' : 'deterministic',
            ],
        ]);

        $programs = $useAi
            ? $this->extractWithAi($body, $institution, $source->url)
            : $this->extractBachelorPrograms($body);

        if ($programs === []) {
            throw new \RuntimeException('The discovery agent found no undergraduate programme candidates.');
        }

        $excerptLines = [];
        foreach ($programs as $program) {
            $excerptLines[] = $program['name']
                .(!empty($program['evidence_excerpt']) ? ' — '.$program['evidence_excerpt'] : '');
        }

        $excerpt = ($useAi ? 'AI extraction' : 'Discovery agent')
            .' extracted '.count($programs).' undergraduate programme candidates from '
            .$source->url."\n\n"
            .implode("\n", $excerptLines);

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
            'entity_type' => $definition['entity_type'],
            'source_type' => $useAi ? 'ai_extraction' : 'automated_discovery',
            'source_id' => $source->id,
            'status' => 'validated',
            'received_at' => now(),
            'notes' => ($useAi ? 'AI extraction proposal run. ' : 'Automated discovery test run. ')
                .'Candidates were collected from an official institutional source and remain non-canonical until governed approval.',
        ]);

        $candidateRows = 0;
        $duplicateRows = 0;

        foreach ($programs as $index => $program) {
            $payload = [
                'name' => $program['name'],
                'slug' => Str::slug($program['name']),
                'program_level' => $program['program_level'] ?? 'undergraduate',
                'award' => $program['award'],
                'discipline' => $program['discipline'],
                'status' => $program['status'] ?? 'active',
                'description' => $useAi
                    ? 'AI-extracted candidate from the official institutional programme source.'
                    : 'Discovered from the official CUI Lahore academic programme listing.',
                '_provenance' => [
                    'method' => $useAi ? 'ai_extraction' : 'deterministic',
                    'source_id' => $source->id,
                    'snapshot_id' => $snapshot->id,
                    'evidence_id' => $evidence->id,
                    'confidence' => $program['confidence'] ?? 0.9500,
                    'evidence_excerpt' => $program['evidence_excerpt'] ?? null,
                ],
            ];

            $duplicate = app(ImportDuplicateDetectorService::class)->find(
                'program',
                $institution->id,
                $payload['slug'],
                $batch->id
            );

            $status = $duplicate ? 'duplicate' : 'candidate';
            $matchState = $duplicate ? 'duplicate' : 'unresolved';

            ImportRow::create([
                'import_batch_id' => $batch->id,
                'row_number' => $index + 1,
                'status' => $status,
                'match_state' => $matchState,
                'parent_subject_type' => Institution::class,
                'parent_subject_id' => $institution->id,
                'payload' => $payload,
                'normalized_payload' => $payload,
                'validation_errors' => $duplicate ? [$duplicate['message']] : null,
                'notes' => 'Evidence #'.$evidence->id.' from '.$source->url
                    .($useAi ? '; AI confidence '.number_format((float) ($program['confidence'] ?? 0), 4) : ''),
            ]);

            Assertion::create([
                'evidence_id' => $evidence->id,
                'subject_type' => Institution::class,
                'subject_id' => $institution->id,
                'attribute' => 'candidate_program',
                'value' => $program['name'],
                'verification_state' => $useAi ? 'ai_extracted' : 'discovered',
                'confidence' => $program['confidence'] ?? 0.9500,
            ]);

            if ($status === 'candidate') {
                $candidateRows++;
            } else {
                $duplicateRows++;
            }
        }

        $batch->update([
            'total_rows' => count($programs),
            'valid_rows' => count($programs),
            'invalid_rows' => 0,
            'candidate_rows' => $candidateRows,
            'completed_at' => now(),
        ]);

        return [
            'profile' => $profile,
            'institution_id' => $institution->id,
            'source_id' => $source->id,
            'snapshot_id' => $snapshot->id,
            'evidence_id' => $evidence->id,
            'batch_id' => $batch->id,
            'total_rows' => count($programs),
            'candidate_rows' => $candidateRows,
            'duplicate_rows' => $duplicateRows,
            'extraction_method' => $useAi ? 'ai_extraction' : 'deterministic',
        ];
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

    private function extractBachelorPrograms(string $html): array
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $programs = [];

        foreach ($xpath->query('//a') as $link) {
            $name = trim(preg_replace('/\s+/', ' ', $link->textContent ?? ''));

            if (!preg_match('/^Bachelor\b/i', $name)) {
                continue;
            }

            if (stripos($name, 'Visit Department') !== false) {
                continue;
            }

            $name = preg_replace('/^Bachelor of Science\(BS\)/i', 'Bachelor of Science (BS)', $name);
            $award = stripos($name, 'Bachelor of Science') === 0 ? 'BS' : 'Bachelor';

            $discipline = $name;
            if (preg_match('/\bin\s+(.+)$/i', $name, $match)) {
                $discipline = trim($match[1]);
            } elseif (preg_match('/^Bachelor of\s+(.+)$/i', $name, $match)) {
                $discipline = trim($match[1]);
            }

            $key = Str::lower(Str::slug($name));
            $programs[$key] = [
                'name' => $name,
                'award' => $award,
                'discipline' => $discipline,
                'program_level' => 'undergraduate',
                'status' => 'active',
                'confidence' => 0.9500,
                'evidence_excerpt' => $name,
            ];
        }

        libxml_clear_errors();

        return array_values($programs);
    }
}
