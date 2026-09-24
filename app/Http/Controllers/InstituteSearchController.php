<?php

namespace App\Http\Controllers;

use App\Services\InstituteSearchCatalogService;
use App\Services\InstituteSearchFilterService;
use App\Services\InstituteSearchQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstituteSearchController
{
    public function __invoke(Request $request, InstituteSearchQueryService $queryService, InstituteSearchFilterService $filterService, InstituteSearchCatalogService $catalog): View
    {
        $keys = ['q', 'type', 'province', 'district', 'city', 'program', 'program_level', 'delivery_mode', 'admission_status'];
        $filters = [];

        foreach ($keys as $key) {
            $filters[$key] = trim((string) $request->query($key, ''));
        }

        $institutions = $filterService->apply($queryService->build($filters['q']), $filters)
            ->withCount([
                'campuses' => fn ($campus) => $campus->where('status', 'active'),
                'programs' => fn ($program) => $program->where('status', 'active'),
            ])
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(24)
            ->withQueryString();

        return view('institutes.search', array_merge([
            'institutions' => $institutions,
            'query' => $filters['q'],
            'type' => $filters['type'],
            'province' => $filters['province'],
            'district' => $filters['district'],
            'city' => $filters['city'],
            'program' => $filters['program'],
            'programLevel' => $filters['program_level'],
            'deliveryMode' => $filters['delivery_mode'],
            'admissionStatus' => $filters['admission_status'],
        ], $catalog->filters()));
    }
}
