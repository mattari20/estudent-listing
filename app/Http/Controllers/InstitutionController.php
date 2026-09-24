<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Services\InstituteGeographyContextService;
use Illuminate\View\View;

class InstitutionController
{
    public function index(): View
    {
        $institutions = Institution::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->paginate(24);

        return view('institutes.index', compact('institutions'));
    }

    public function show(Institution $institution, InstituteGeographyContextService $geographyContext): View
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
                                ->with(['eligibilityRules', 'meritRecords', 'scholarships', 'feeRecords'])
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

        return view('institutes.show', compact('institution'));
    }
}
