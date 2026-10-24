<?php

namespace App\Http\Controllers;

use App\Models\InstituteDiscoveryRunOutcome;
use App\Models\ImportBatch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstituteDiscoveryRunHistoryController
{
    public function __invoke(Request $request): View
    {
        $outcomes = InstituteDiscoveryRunOutcome::query()
            ->with(['source', 'snapshot'])
            ->orderByDesc('occurred_at')
            ->paginate(50)
            ->withQueryString();

        $batchIds = $outcomes->getCollection()
            ->map(fn (InstituteDiscoveryRunOutcome $outcome) => (int) ($outcome->details['batch_id'] ?? 0))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $batches = ImportBatch::query()
            ->whereIn('id', $batchIds)
            ->with('rows')
            ->get()
            ->keyBy('id');

        return view('institutes.operations.discovery-run-history', [
            'outcomes' => $outcomes,
            'batches' => $batches,
        ]);
    }
}
