<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Source;
use App\Services\EnrichmentImportSchemaService;
use App\Services\ImportDuplicateDetectorService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstituteEnrichmentImportController
{
    public function create(): View
    {
        $institutions = Institution::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('institutes.enrichment-import', compact('institutions'));
    }

    public function template(Request $request, EnrichmentImportSchemaService $schemas)
    {
        $entityType = $request->string('entity_type')->toString() ?: 'campus';
        $schemas->profile($entityType);

        return response(
            $schemas->template($entityType),
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $entityType . '-enrichment-template-v1.0.csv"',
            ],
        );
    }

    public function store(
        Request $request,
        EnrichmentImportSchemaService $schemas,
        ImportDuplicateDetectorService $duplicates,
    ): View {
        $validated = $request->validate([
            'source_name' => ['required', 'string', 'max:255'],
            'source_type' => ['required', 'string', 'max:100'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'entity_type' => ['required', 'in:campus,program'],
            'schema_version' => ['required', 'in:1.0'],
            'parent_institution_id' => ['required', 'integer', 'exists:institutions,id'],
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $institution = Institution::query()
            ->whereKey($validated['parent_institution_id'])
            ->where('status', 'active')
            ->firstOrFail();

        $profile = $schemas->profile($validated['entity_type']);

        $source = Source::create([
            'source_type' => $validated['source_type'],
            'name' => $validated['source_name'],
            'url' => $validated['source_url'] ?? null,
            'status' => 'active',
        ]);

        $batch = ImportBatch::create([
            'batch_key' => (string) Str::uuid(),
            'entity_type' => $validated['entity_type'],
            'source_type' => $validated['source_type'],
            'source_id' => $source->id,
            'status' => 'processing',
            'received_at' => now(),
            'notes' => 'enrichment_schema=' . $validated['entity_type'] . '-csv@' . $profile['version']
                . '; parent_institution_id=' . $institution->id,
        ]);

        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $rowNumber = 1;
        $valid = 0;
        $invalid = 0;
        $candidate = 0;
        $duplicateCount = 0;
        $seenSlugs = [];
        $pending = [];

        if ($header) {
            $header = array_map(fn ($value) => trim((string) $value), $header);
            if (isset($header[0])) {
                $header[0] = preg_replace('/^\\xEF\\xBB\\xBF/', '', $header[0]);
            }
        }

        $headerErrors = $header
            ? $schemas->validateHeaders($header, $validated['entity_type'])
            : ['CSV header is missing.'];

        if ($headerErrors) {
            fclose($handle);

            $batch->update([
                'status' => 'failed',
                'completed_at' => now(),
                'notes' => implode('; ', $headerErrors),
            ]);

            return view('institutes.import-result', compact('batch', 'institution'));
        }

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            $payload = [];
            foreach ($header as $index => $key) {
                $payload[$key] = isset($data[$index]) ? trim((string) $data[$index]) : null;
            }

            $normalized = $schemas->normalize($payload, $validated['entity_type']);
            $rowErrors = $schemas->validateRow($normalized, $validated['entity_type'], $seenSlugs);
            $slug = (string) ($normalized['slug'] ?? '');
            $duplicate = null;

            if ($slug !== '' && isset($seenSlugs[$slug])) {
                $duplicate = [
                    'kind' => 'batch',
                    'label' => 'Current batch',
                    'id' => null,
                    'message' => 'The same record appears more than once in this CSV. The duplicate will not be published automatically.',
                ];
            } elseif ($slug !== '') {
                $duplicate = $duplicates->find($validated['entity_type'], $institution->id, $slug, $batch->id);
            }

            if ($slug !== '') {
                $seenSlugs[$slug] = true;
            }

            if ($duplicate) {
                $status = 'duplicate';
                $rowErrors = array_values(array_unique(array_merge($rowErrors, [$duplicate['message']])));
            } else {
                $status = $rowErrors ? 'invalid' : 'candidate';
            }

            if ($status === 'invalid') {
                $invalid++;
            } elseif ($status === 'duplicate') {
                $duplicateCount++;
            } elseif ($status === 'candidate') {
                $valid++;
                $candidate++;
            }

            $pending[] = [
                'import_batch_id' => $batch->id,
                'row_number' => $rowNumber,
                'status' => $status,
                'match_state' => $duplicate ? 'duplicate' : 'unresolved',
                'parent_subject_type' => Institution::class,
                'parent_subject_id' => $institution->id,
                'payload' => json_encode($payload),
                'normalized_payload' => json_encode($normalized),
                'validation_errors' => $rowErrors ? json_encode($rowErrors) : null,
                'notes' => $duplicate ? $duplicate['message'] : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($pending) >= 500) {
                ImportRow::insert($pending);
                $pending = [];
            }
        }

        fclose($handle);

        if ($pending) {
            ImportRow::insert($pending);
        }

        $batch->update([
            'status' => 'validated',
            'total_rows' => $rowNumber - 1,
            'valid_rows' => $valid,
            'invalid_rows' => $invalid,
            'candidate_rows' => $candidate,
            'duplicate_rows' => $duplicateCount,
            'completed_at' => now(),
        ]);

        return view('institutes.import-result', compact('batch', 'institution'));
    }
}
