<?php

namespace App\Http\Controllers;

use App\Models\AdmissionIntake;
use App\Models\Geography;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstituteSearchController
{
    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));
        $province = trim((string) $request->query('province', ''));
        $district = trim((string) $request->query('district', ''));
        $city = trim((string) $request->query('city', ''));
        $program = trim((string) $request->query('program', ''));
        $programLevel = trim((string) $request->query('program_level', ''));
        $deliveryMode = trim((string) $request->query('delivery_mode', ''));
        $admissionStatus = trim((string) $request->query('admission_status', ''));

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
            ->when($province !== '', fn ($query) => $query->whereHas('campuses.geography.parent.parent', fn ($geographies) => $geographies->where('name', $province)->where('type', 'province')))
            ->when($district !== '', fn ($query) => $query->whereHas('campuses.geography.parent', fn ($geographies) => $geographies->where('name', $district)->where('type', 'district')))
            ->when($city !== '', fn ($query) => $query->whereHas('campuses.geography', fn ($geographies) => $geographies->where('name', $city)->where('type', 'city')))
            ->when($program !== '', fn ($query) => $query->whereHas('programs', fn ($programs) => $programs
                ->where('status', 'active')
                ->where(function ($programQuery) use ($program): void {
                    $programQuery
                        ->where('name', 'like', "%{$program}%")
                        ->orWhere('discipline', 'like', "%{$program}%");
                })))
            ->when($programLevel !== '', fn ($query) => $query->whereHas('programs', fn ($programs) => $programs->where('status', 'active')->where('program_level', $programLevel)))
            ->when($deliveryMode !== '', fn ($query) => $query->whereHas('programs.offerings', fn ($offerings) => $offerings->where('status', 'active')->where('delivery_mode', $deliveryMode)))
            ->when($admissionStatus !== '', fn ($query) => $query->whereHas('programs.offerings.admissionIntakes', fn ($intakes) => $intakes->where('status', $admissionStatus)))
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

        $provinces = Geography::query()
            ->where('status', 'active')
            ->where('type', 'province')
            ->orderBy('name')
            ->pluck('name');

        $districts = Geography::query()
            ->where('status', 'active')
            ->where('type', 'district')
            ->orderBy('name')
            ->pluck('name');

        $cities = Geography::query()
            ->where('status', 'active')
            ->where('type', 'city')
            ->orderBy('name')
            ->pluck('name');

        $programLevels = Program::query()
            ->where('status', 'active')
            ->whereNotNull('program_level')
            ->distinct()
            ->orderBy('program_level')
            ->pluck('program_level');

        $deliveryModes = ProgramOffering::query()
            ->where('status', 'active')
            ->whereNotNull('delivery_mode')
            ->distinct()
            ->orderBy('delivery_mode')
            ->pluck('delivery_mode');

        $admissionStatuses = AdmissionIntake::query()
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view('institutes.search', compact(
            'institutions',
            'query',
            'type',
            'province',
            'district',
            'city',
            'program',
            'programLevel',
            'deliveryMode',
            'admissionStatus',
            'types',
            'provinces',
            'districts',
            'cities',
            'programLevels',
            'deliveryModes',
            'admissionStatuses'
        ))->with('query', $q);
    }
}
