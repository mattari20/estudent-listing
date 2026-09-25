<?php

namespace App\Services;

use App\Models\AdmissionIntake;
use App\Models\Campus;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;

class ImportDependencyContextService
{
    public function build(ImportRow $row): array
    {
        $context = ['parent' => null, 'chain' => []];

        $type = $row->parent_subject_type;
        $id = $row->parent_subject_id;

        if (!is_string($type) || !is_numeric($id) || (int) $id < 1) {
            return $context;
        }

        $allowedTypes = [
            AdmissionIntake::class,
            Program::class,
            ProgramOffering::class,
            Campus::class,
            Institution::class,
        ];

        if (!in_array($type, $allowedTypes, true) || !class_exists($type)) {
            return $context;
        }

        $parent = $type::query()->find((int) $id);

        if (!$parent) {
            return $context;
        }

        $context['parent'] = $parent;

        if ($parent instanceof ProgramOffering) {
            $program = $parent->program;
            $campus = $parent->campus;
            $institution = $program?->institution;
            $context['chain'] = array_values(array_filter([
                $institution,
                $campus,
                $program,
                $parent,
            ]));
        } elseif ($parent instanceof Program) {
            $institution = $parent->institution;
            $context['chain'] = array_values(array_filter([
                $institution,
                $parent,
            ]));
        } elseif ($parent instanceof Campus) {
            $institution = $parent->institution;
            $context['chain'] = array_values(array_filter([
                $institution,
                $parent,
            ]));
        } elseif ($parent instanceof AdmissionIntake) {
            $offering = $parent->offering;
            $program = $offering?->program;
            $institution = $program?->institution;
            $context['chain'] = array_values(array_filter([
                $institution,
                $program,
                $offering,
                $parent,
            ]));
        } elseif ($parent instanceof Institution) {
            $context['chain'] = [$parent];
        }

        return $context;
    }
}
