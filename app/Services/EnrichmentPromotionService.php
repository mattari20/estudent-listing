<?php

namespace App\Services;

use App\Models\AdmissionIntake;
use App\Models\ImportRow;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use RuntimeException;

class EnrichmentPromotionService
{
    public function promote(ImportRow $row): object
    {
        $type = $row->batch->entity_type;

        return match ($type) {
            'campus', 'program', 'program_offering' => app(StructuredEnrichmentService::class)->promote($row),
            'admission_intake', 'eligibility_rule', 'merit_record', 'selection_outcome' => app(AcademicEnrichmentService::class)->promote($row),
            'fee_record', 'scholarship', 'accreditation' => app(FinancialAccreditationEnrichmentService::class)->promote($row),
            default => throw new RuntimeException("Unsupported enrichment entity type: {$type}"),
        };
    }

    public function validateParent(ImportRow $row): void
    {
        $type = $row->batch->entity_type;
        $parentType = $row->parent_subject_type;

        $allowedParentTypes = match ($type) {
            'campus', 'program' => [Institution::class],
            'program_offering' => [Program::class],
            'admission_intake' => [ProgramOffering::class],
            'eligibility_rule', 'merit_record', 'selection_outcome' => [AdmissionIntake::class],
            'fee_record', 'scholarship' => [ProgramOffering::class, AdmissionIntake::class],
            'accreditation' => [Institution::class, ProgramOffering::class],
            default => null,
        };

        if ($allowedParentTypes === null) {
            return;
        }

        if (! in_array($parentType, $allowedParentTypes, true)) {
            $message = match ($type) {
                'campus', 'program' => 'Campus/program imports require an Institution parent.',
                'program_offering' => 'Program offering imports require a Program parent.',
                'admission_intake' => 'Admission intake imports require a Program Offering parent.',
                'eligibility_rule', 'merit_record', 'selection_outcome' => "{$type} imports require an Admission Intake parent.",
                'fee_record', 'scholarship' => "{$type} imports require a Program Offering or Admission Intake parent.",
                'accreditation' => 'Accreditation imports require an Institution or Program Offering parent.',
            };

            throw new RuntimeException($message);
        }

        if (! $row->parent_subject_id) {
            throw new RuntimeException('A canonical parent ID is required before this import row can be approved or promoted.');
        }

        $parentExists = match ($parentType) {
            Institution::class => Institution::query()->whereKey($row->parent_subject_id)->exists(),
            Program::class => Program::query()->whereKey($row->parent_subject_id)->exists(),
            ProgramOffering::class => ProgramOffering::query()->whereKey($row->parent_subject_id)->exists(),
            AdmissionIntake::class => AdmissionIntake::query()->whereKey($row->parent_subject_id)->exists(),
            default => false,
        };

        if (! $parentExists) {
            $parentLabel = class_basename($parentType);

            throw new RuntimeException(
                "The selected canonical {$parentLabel} parent does not exist. Refresh parent resolution and select an existing canonical record before approval or promotion."
            );
        }
    }
}
