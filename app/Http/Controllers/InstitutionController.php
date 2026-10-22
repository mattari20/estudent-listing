<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Services\InstituteGeographyContextService;
use App\Services\InstituteSeoService;
use Illuminate\View\View;

class InstitutionController
{
    public function index(): View
    {
        // Show the current canonical directory population on the first page
        // while retaining smaller page sizes as an explicit user preference.
        $perPage = (int) request()->query('per_page', 24);
        if (! in_array($perPage, [9, 12, 24, 48], true)) {
            $perPage = 24;
        }

        $institutions = Institution::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('institutes.index', compact('institutions'));
    }

    public function show(Institution $institution, InstituteGeographyContextService $geographyContext, InstituteSeoService $seo): View
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
                        ->orderBy('name'),
                ])
                ->with([
                    'externalRankings' => fn ($query) => $query
                        ->whereNotIn('status', ['rejected', 'withdrawn'])
                        ->orderByDesc('year'),
                    'recommendations' => fn ($query) => $query
                        ->where('status', 'published'),
                ])
                ->orderBy('name'),
            'accreditations' => fn ($query) => $query
                ->whereNotIn('status', ['rejected', 'withdrawn'])
                ->orderBy('name'),
            'externalRankings' => fn ($query) => $query
                ->whereNotIn('status', ['rejected', 'withdrawn'])
                ->orderByDesc('year'),
            'recommendations' => fn ($query) => $query
                ->where('status', 'published'),
        ]);

        foreach ($institution->campuses as $campus) {
            $campus->setAttribute('geography_context', $geographyContext->forCampus($campus));
        }

        $institutionSchema = $seo->institutionSchema($institution);
        $breadcrumbSchema = $seo->breadcrumb($institution->name, route('institutes.show', $institution));

        return view('institutes.show', compact('institution', 'institutionSchema', 'breadcrumbSchema'));
    }
}
