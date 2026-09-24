<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Services\InstituteSearchFilterService;
use App\Services\InstituteSearchQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionApiController extends Controller
{
    public function index(
        Request $request,
        InstituteSearchQueryService $queryService,
        InstituteSearchFilterService $filterService,
    ): JsonResponse {
        $keys = [
            'q',
            'type',
            'province',
            'district',
            'city',
            'program',
            'program_level',
            'delivery_mode',
            'admission_status',
        ];

        $filters = [];

        foreach ($keys as $key) {
            $filters[$key] = trim((string) $request->query($key, ''));
        }

        $perPage = min(max((int) $request->query('per_page', 24), 1), 100);

        $items = $filterService
            ->apply($queryService->build($filters['q']), $filters)
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json($items);
    }

    public function show(Institution $institution): JsonResponse
    {
        abort_unless($institution->status === 'active', 404);

        $institution->load([
            'campuses' => fn ($query) => $query
                ->where('status', 'active')
                ->with(['geography' => fn ($geography) => $geography->where('status', 'active')])
                ->orderBy('name'),
            'programs' => fn ($query) => $query
                ->where('status', 'active')
                ->with([
                    'offerings' => fn ($offeringQuery) => $offeringQuery
                        ->where('status', 'active')
                        ->whereHas('campus', fn ($campus) => $campus->where('status', 'active'))
                        ->with([
                            'admissionIntakes' => fn ($intakeQuery) => $intakeQuery
                                ->whereNotIn('status', ['cancelled', 'suspended'])
                                ->with([
                                    'eligibilityRules' => fn ($query) => $query->whereNotIn('status', ['rejected', 'withdrawn']),
                                    'meritRecords' => fn ($query) => $query->whereNotIn('status', ['rejected', 'withdrawn']),
                                    'scholarships' => fn ($query) => $query->whereNotIn('status', ['rejected', 'withdrawn']),
                                    'feeRecords' => fn ($query) => $query->whereNotIn('status', ['superseded']),
                                ])
                                ->orderByDesc('application_close_at'),
                            'scholarships' => fn ($scholarshipQuery) => $scholarshipQuery
                                ->whereNotIn('status', ['rejected', 'withdrawn'])
                                ->orderBy('name'),
                            'feeRecords' => fn ($feeQuery) => $feeQuery
                                ->whereNotIn('status', ['superseded'])
                                ->orderBy('fee_type'),
                            'accreditations' => fn ($accreditationQuery) => $accreditationQuery
                                ->whereNotIn('status', ['rejected', 'withdrawn'])
                                ->orderBy('name'),
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
