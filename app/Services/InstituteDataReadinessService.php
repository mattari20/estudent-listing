<?php

namespace App\Services;

use App\Models\AdmissionIntake;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;

class InstituteDataReadinessService
{
    /**
     * Return a read-only structural readiness summary for real-data population.
     *
     * This service deliberately measures canonical integrity and coverage only.
     * It never creates, updates, deletes, activates, deactivates, or repairs data.
     */
    public function summarize(): array
    {
        $institutions = Institution::count();
        $activeInstitutions = Institution::where('status', 'active')->count();
        $inactiveInstitutions = $institutions - $activeInstitutions;

        $campuses = Campus::count();
        $activeCampuses = Campus::where('status', 'active')->count();
        $inactiveCampuses = $campuses - $activeCampuses;

        $programs = Program::count();
        $activePrograms = Program::where('status', 'active')->count();
        $inactivePrograms = $programs - $activePrograms;

        $offerings = ProgramOffering::count();
        $activeOfferings = ProgramOffering::where('status', 'active')->count();
        $inactiveOfferings = $offerings - $activeOfferings;

        $intakes = AdmissionIntake::count();

        $findings = [
            'active_institutions_without_active_campus' => Institution::query()
                ->where('status', 'active')
                ->whereDoesntHave('campuses', fn ($query) => $query->where('status', 'active'))
                ->count(),

            'active_campuses_without_active_geography' => Campus::query()
                ->where('status', 'active')
                ->whereDoesntHave('geography', fn ($query) => $query->where('status', 'active'))
                ->count(),

            'active_programs_without_active_institution' => Program::query()
                ->where('status', 'active')
                ->whereDoesntHave('institution', fn ($query) => $query->where('status', 'active'))
                ->count(),

            'active_offerings_without_active_program' => ProgramOffering::query()
                ->where('status', 'active')
                ->whereDoesntHave('program', fn ($query) => $query->where('status', 'active'))
                ->count(),

            'active_offerings_without_active_campus' => ProgramOffering::query()
                ->where('status', 'active')
                ->whereDoesntHave('campus', fn ($query) => $query->where('status', 'active'))
                ->count(),

            'intakes_without_active_offering' => AdmissionIntake::query()
                ->whereDoesntHave('offering', fn ($query) => $query->where('status', 'active'))
                ->count(),
        ];

        $blockingFindings = array_sum($findings);

        return [
            'scope' => 'read_only_structural_readiness',
            'population' => [
                'institutions' => $institutions,
                'active_institutions' => $activeInstitutions,
                'inactive_institutions' => $inactiveInstitutions,
                'campuses' => $campuses,
                'active_campuses' => $activeCampuses,
                'inactive_campuses' => $inactiveCampuses,
                'programs' => $programs,
                'active_programs' => $activePrograms,
                'inactive_programs' => $inactivePrograms,
                'program_offerings' => $offerings,
                'active_program_offerings' => $activeOfferings,
                'inactive_program_offerings' => $inactiveOfferings,
                'admission_intakes' => $intakes,
            ],
            'integrity' => $findings,
            'blocking_findings' => $blockingFindings,
            'structurally_ready' => $blockingFindings === 0,
            'mutation_performed' => false,
        ];
    }
}
