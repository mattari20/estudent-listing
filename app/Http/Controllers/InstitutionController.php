<?php

namespace App\Http\Controllers;

use App\Models\Institution;
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

    public function show(Institution $institution): View
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
                        ->orderBy('intake_label'),
                ])
                ->orderBy('name'),
        ]);

        return view('institutes.show', compact('institution'));
    }
}
