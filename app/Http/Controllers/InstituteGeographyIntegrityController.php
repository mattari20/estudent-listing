<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Services\InstituteGeographyIntegrityService;
use Illuminate\View\View;

class InstituteGeographyIntegrityController
{
    public function __invoke(InstituteGeographyIntegrityService $integrity): View
    {
        $institutions = Institution::query()
            ->where('status', 'active')
            ->with([
                'campuses' => fn ($query) => $query
                    ->where('status', 'active')
                    ->with('geography'),
            ])
            ->orderBy('name')
            ->paginate(25);

        $institutions->getCollection()->transform(function ($institution) use ($integrity) {
            $institution->setAttribute(
                'integrity_findings',
                $integrity->inspectInstitution($institution)
            );

            return $institution;
        });

        return view('institutes.operations.integrity', compact('institutions'));
    }
}
