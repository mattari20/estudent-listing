<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = min(max((int) $request->query('per_page', 24), 1), 100);

        $items = Institution::query()
            ->where('status', 'active')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($search) use ($q): void {
                    $search
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('official_name', 'like', "%{$q}%")
                        ->orWhere('short_name', 'like', "%{$q}%")
                        ->orWhereHas('names', function ($names) use ($q): void {
                            $names->where('name', 'like', "%{$q}%");
                        });
                });
            })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($items);
    }

    public function show(Institution $institution): JsonResponse
    {
        abort_unless($institution->status === 'active', 404);

        $institution->load([
            'campuses' => fn ($query) => $query
                ->where('status', 'active')
                ->with('geography')
                ->orderBy('name'),
            'programs' => fn ($query) => $query
                ->where('status', 'active')
                ->with([
                    'offerings' => fn ($offeringQuery) => $offeringQuery
                        ->where('status', 'active')
                        ->with([
                            'admissionIntakes.eligibilityRules',
                            'admissionIntakes.meritRecords',
                            'admissionIntakes.scholarships',
                            'admissionIntakes.feeRecords',
                            'scholarships',
                            'feeRecords',
                            'accreditations',
                        ])
                        ->orderBy('intake_label'),
                ])
                ->orderBy('name'),
            'accreditations' => fn ($query) => $query
                ->whereNotIn('status', ['rejected', 'withdrawn'])
                ->orderBy('name'),
        ]);

        return response()->json($institution);
    }
}
