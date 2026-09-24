<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Geography;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstituteSearchController
{
    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));
        $city = trim((string) $request->query('city', ''));
        $program = trim((string) $request->query('program', ''));

        $institutions = Institution::query()
            ->where('status', 'active')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($search) use ($q): void {
                    $search
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('official_name', 'like', "%{$q}%")
                        ->orWhere('short_name', 'like', "%{$q}%")
                        ->orWhereHas('names', fn ($names) => $names->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('programs', fn ($programs) => $programs
                            ->where('status', 'active')
                            ->where(function ($programQuery) use ($q): void {
                                $programQuery
                                    ->where('name', 'like', "%{$q}%")
                                    ->orWhere('discipline', 'like', "%{$q}%")
                                    ->orWhere('program_level', 'like', "%{$q}%");
                            }))
                        ->orWhereHas('campuses.geography', fn ($geographies) => $geographies
                            ->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($type !== '', fn ($query) => $query->where('institution_type', $type))
            ->when($city !== '', fn ($query) => $query->whereHas('campuses.geography', fn ($geographies) => $geographies->where('name', $city)))
            ->when($program !== '', fn ($query) => $query->whereHas('programs', fn ($programs) => $programs
                ->where('status', 'active')
                ->where(function ($programQuery) use ($program): void {
                    $programQuery
                        ->where('name', 'like', "%{$program}%")
                        ->orWhere('discipline', 'like', "%{$program}%");
                })))
            ->withCount(['campuses', 'programs'])
            ->with(['campuses.geography'])
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        $types = Institution::query()
            ->where('status', 'active')
            ->whereNotNull('institution_type')
            ->distinct()
            ->orderBy('institution_type')
            ->pluck('institution_type');

        $cities = Geography::query()
            ->where('status', 'active')
            ->where('type', 'city')
            ->orderBy('name')
            ->pluck('name');

        return view('institutes.search', compact('institutions', 'query', 'type', 'city', 'program', 'types', 'cities'))
            ->with('query', $q);
    }
}
