<?php

namespace App\\Http\\Controllers;

use App\\Models\\InstituteDiscoveryRunOutcome;
use Illuminate\\Http\\Request;
use Illuminate\\View\\View;

class InstituteDiscoveryRunHistoryController
{
    public function __invoke(Request $request): View
    {
        $outcomes = InstituteDiscoveryRunOutcome::query()
            ->with(['source', 'snapshot'])
            ->orderByDesc('occurred_at')
            ->paginate(50)
            ->withQueryString();

        return view('institutes.operations.discovery-run-history', [
            'outcomes' => $outcomes,
        ]);
    }
}
