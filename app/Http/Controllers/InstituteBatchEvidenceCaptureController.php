<?php

namespace App\Http\Controllers;

use App\Models\Assertion;
use App\Models\Evidence;
use App\Models\IdentityResolutionDecision;
use App\Models\ImportBatch;
use App\Models\ImportConflict;
use App\Models\ImportRow;
use App\Models\Source;
use App\Services\ImportPromotionAuditService;
use App\Services\InstituteSourceEvidenceCaptureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class InstituteBatchEvidenceCaptureController
{
    public function create(ImportBatch $importBatch): View
    {
        abort_unless($importBatch->entity_type === 'institution', 404);

        $rows = $importBatch->rows()
            ->where('status', 'candidate')
            ->orderBy('row_number')
            ->get(['id', 'row_number', 'payload', 'normalized_payload']);

        return view('institutes.operations.batch-evidence-capture', [
            'importBatch' => $importBatch,
            'rows' => $rows,
        ]);
    }

    public function store(
        Request $request,
        ImportBatch $importBatch,
        InstituteSourceEvidenceCaptureService $capture,
        ImportPromotionAuditService $audit
    ): RedirectResponse {
        abort_unless($importBatch->entity_type === 'institution', 404);

        $request->validate([
            'evidence_csv' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
        ]);

        $file = $request->file('evidence_csv');
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            return back()->withErrors(['evidence_csv' => 'The uploaded CSV could not be read.']);
        }

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            return back()->withErrors(['evidence_csv' => 'The CSV is empty.']);
        }
        $header = array_map(static fn ($value) => trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $value)), $header);
        $required = ['import_row_id', 'official_source_url', 'source_excerpt'];
        if (array_values($header) !== $required) {
            fclose($handle);
            return back()->withErrors(['evidence_csv' => 'Required header, in this exact order: import_row_id,official_source_url,source_excerpt']);
        }

        $records = [];
        $line = 1;
        while (($values = fgetcsv($handle)) !== false) {
            $line++;
            if ($values === [null] || $values === []) {
                continue;
            }
            if (count($values) !== count($required)) {
                fclose($handle);
                return back()->withErrors(['evidence_csv' => 'CSV line '.$line.' must contain exactly three columns.']);
            }
            $record = array_combine($required, array_map(static fn ($value) => trim((string) $value), $values));
            $validator = Validator::make($record, [
                'import_row_id' => ['required', 'integer', 'distinct'],
                'official_source_url' => ['required', 'url', 'max:2048', 'regex:/^https:\/\//i'],
                'source_excerpt' => ['required', 'string', 'min:20', 'max:10000'],
            ]);
            if ($validator->fails()) {
                fclose($handle);
                return back()->withErrors(['evidence_csv' => 'CSV line '.$line.': '.$validator->errors()->first()]);
            }
            $records[] = $record;
        }
        fclose($handle);

        if ($records === []) {
            return back()->withErrors(['evidence_csv' => 'The CSV contains no evidence rows.']);
        }

        // Validate every row before writing anything, so a bad row cannot leave a partial batch.
        $prepared = [];
        foreach ($records as $record) {
            $row = ImportRow::query()->where('import_batch_id', $importBatch->id)->find($record['import_row_id']);
            if (!$row || $row->status !== 'candidate') {
                return back()->withErrors(['evidence_csv' => 'Import row '.$record['import_row_id'].' is not a candidate in this batch. No evidence was recorded.']);
            }
            $decision = IdentityResolutionDecision::query()
                ->where('import_row_id', $row->id)
                ->whereIn('decision', ['approved_new', 'approved_match'])
                ->latest('id')->first();
            if (!$decision) {
                return back()->withErrors(['evidence_csv' => 'Import row '.$row->id.' lacks an approved identity decision. No evidence was recorded.']);
            }
            if (ImportConflict::query()->where('import_row_id', $row->id)->where('status', 'open')->exists()) {
                return back()->withErrors(['evidence_csv' => 'Import row '.$row->id.' has an open conflict. Resolve it before capturing evidence. No evidence was recorded.']);
            }
            $hasEvidence = Assertion::query()
                ->where('subject_type', ImportRow::class)
                ->where('subject_id', $row->id)
                ->whereHas('evidence', fn ($query) => $query->where('status', 'active'))
                ->exists();
            if ($hasEvidence) {
                return back()->withErrors(['evidence_csv' => 'Import row '.$row->id.' already has active evidence. Remove it from this CSV to avoid duplicates. No evidence was recorded.']);
            }
            $payload = $row->normalized_payload ?? $row->payload ?? [];
            $name = (string) ($payload['official_name'] ?? $payload['name'] ?? '');
            if (trim($name) === '') {
                return back()->withErrors(['evidence_csv' => 'Import row '.$row->id.' has no institution name. No evidence was recorded.']);
            }
            $prepared[] = ['row' => $row, 'record' => $record, 'name' => $name];
        }

        DB::transaction(function () use ($prepared, $request, $capture, $audit): void {
            foreach ($prepared as $item) {
                $row = $item['row'];
                $record = $item['record'];
                $source = Source::create([
                    'source_type' => 'official_institutional',
                    'name' => 'Official source evidence for institution import row '.$row->id,
                    'url' => $record['official_source_url'],
                    'status' => 'active',
                ]);
                $evidence = $capture->capture($source, $record['official_source_url'], $record['source_excerpt'], null, [[
                    'subject_type' => ImportRow::class,
                    'subject_id' => $row->id,
                    'attribute' => 'institution_identity',
                    'value' => $item['name'],
                    'verification_state' => 'operator_observed',
                    'verified_at' => now()->toDateTimeString(),
                ]]);
                $audit->record(
                    $row,
                    'source_evidence_captured',
                    $request->user()->id,
                    'Operator captured official source evidence using the batch evidence workflow.',
                    ['evidence_id' => $evidence->id, 'reference' => $record['official_source_url']],
                    'source_evidence',
                    $evidence->id
                );
            }
        });

        return redirect()->route('institutes.operations.queue', ['batch_id' => $importBatch->id])
            ->with('status', 'Captured evidence for '.count($prepared).' import rows. No rows were promoted or published.');
    }
}
