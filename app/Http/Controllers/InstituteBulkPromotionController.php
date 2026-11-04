<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Models\ImportConflict;
use App\Models\ImportRow;
use App\Services\BulkImportPromotionService;
use App\Services\EnrichmentPromotionService;
use App\Services\OperatorTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InstituteBulkPromotionController
{
    public function store(Request $request, BulkImportPromotionService $service): RedirectResponse
    {
        $data = $request->validate(['row_ids' => 'required|array|max:100', 'row_ids.*' => 'integer|exists:import_rows,id']);
        $result = $service->promote(array_values(array_unique($data['row_ids'])), $request->user()->id);

        return back()->with('status', "Bulk promotion completed: {$result['promoted']} promoted, {$result['skipped']} skipped, {$result['failed']} failed.");
    }

    public function bulkDecision(Request $request, EnrichmentPromotionService $enrichment, OperatorTransitionService $transitions): RedirectResponse
    {
        $data = $request->validate([
            'row_ids' => 'required|array|min:1|max:100',
            'row_ids.*' => 'integer|distinct|exists:import_rows,id',
            'decision' => 'required|in:approve,reject',
            'reason' => 'required|string|max:2000',
        ]);
        $result = ['approved' => 0, 'rejected' => 0, 'skipped' => 0, 'failed' => 0];

        foreach (array_values(array_unique($data['row_ids'])) as $id) {
            try {
                $row = ImportRow::query()->with('batch')->find($id);
                if (! $row || ! in_array($row->status, ['candidate', 'validated', 'duplicate', 'invalid'], true)) {
                    $result['skipped']++;
                    continue;
                }
                if (ImportConflict::query()->where('import_row_id', $row->id)->where('status', 'open')->exists()) {
                    $result['skipped']++;
                    continue;
                }

                if ($data['decision'] === 'approve') {
                    if ($row->batch?->entity_type === 'institution' || ! in_array($row->batch?->entity_type, ['campus', 'program', 'program_offering', 'admission_intake', 'eligibility_rule', 'merit_record', 'selection_outcome', 'fee_record', 'scholarship', 'accreditation'], true) || $row->status !== 'candidate') {
                        $result['skipped']++;
                        continue;
                    }
                    $transitions->recordPromotionDecision($row, (int) $request->user()->id, 'approved', $data['reason'], 'bulk_approve', $enrichment);
                    $result['approved']++;
                    continue;
                }

                $transitions->rejectRow($row, (int) $request->user()->id, $data['reason'], true);
                $result['rejected']++;
            } catch (\Throwable) {
                $result['failed']++;
            }
        }

        return back()->with('status', "Bulk review complete: {$result['approved']} approved, {$result['rejected']} rejected, {$result['skipped']} skipped (not eligible/conflict), {$result['failed']} failed.");
    }

    public function approveAndPromoteNew(Request $request, ImportBatch $importBatch, BulkImportPromotionService $service): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $result = $service->approveAndPromoteNew($importBatch->id, $request->user()->id, $data['reason']);

        return redirect()->route('institutes.operations.queue', ['batch_id' => $importBatch->id])->with('status', "Bulk new-institution approval completed: {$result['promoted']} promoted, {$result['skipped']} skipped, {$result['matched_or_conflicted']} held for review, {$result['failed']} failed.");
    }
}
