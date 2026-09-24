<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Assertion;

class InstituteProvenanceReadinessService
{
    public function summarize(ImportBatch $batch): array
    {
        $rows = ImportRow::query()->where('import_batch_id', $batch->id)->where('status', 'candidate')->get();
        $rowsWithInvalidLineage = 0;
        $rowsWithoutCompleteAssertion = 0;

        foreach ($rows as $row) {
            $evidences = Evidence::query()->where('status', 'active')
                ->whereHas('assertions', fn ($q) => $q->where('subject_type', ImportRow::class)->where('subject_id', $row->id))
                ->get();

            if ($evidences->isEmpty()) {
                $rowsWithInvalidLineage++;
                $rowsWithoutCompleteAssertion++;
                continue;
            }

            $validEvidence = false;
            foreach ($evidences as $evidence) {
                if (!$evidence->snapshot_id) {
                    continue;
                }
                $snapshot = $evidence->snapshot;
                if (!$snapshot || $snapshot->source_id !== $batch->source_id || $snapshot->status !== 'captured') {
                    continue;
                }
                if ($evidence->content_hash && $snapshot->content_hash !== $evidence->content_hash) {
                    continue;
                }
                $validEvidence = true;
                break;
            }

            if (!$validEvidence) {
                $rowsWithInvalidLineage++;
            }

            $complete = Assertion::query()->whereHas('evidence', fn ($q) => $q->where('status', 'active')->where('source_id', $batch->source_id))
                ->where('subject_type', ImportRow::class)->where('subject_id', $row->id)
                ->whereNotNull('attribute')->whereNotNull('value')->exists();

            if (!$complete) {
                $rowsWithoutCompleteAssertion++;
            }
        }

        $blocking = [
            'candidate_rows_without_snapshot_lineage' => $rowsWithInvalidLineage,
            'candidate_rows_without_complete_assertion' => $rowsWithoutCompleteAssertion,
        ];

        return [
            'scope' => 'read_only_provenance_readiness',
            'batch' => ['id' => $batch->id, 'source_id' => $batch->source_id],
            'candidate_rows' => $rows->count(),
            'blocking_findings' => $blocking,
            'blocking_total' => array_sum($blocking),
            'provenance_ready' => array_sum($blocking) === 0,
            'mutation_performed' => false,
        ];
    }
}
