<?php

namespace App\Services;

use App\Models\Accreditation;
use App\Models\AdmissionIntake;
use App\Models\FeeRecord;
use App\Models\ImportRow;
use App\Models\ProgramOffering;
use App\Models\Scholarship;
use RuntimeException;

class FinancialAccreditationEnrichmentService
{
    public function promote(ImportRow $row): object
    {
        if ($row->status !== 'candidate') {
            throw new RuntimeException('Only candidate import rows can be promoted.');
        }

        $payload = $row->normalized_payload ?? $row->payload ?? [];
        $type = $row->batch->entity_type;

        $entity = match ($type) {
            'fee_record' => $this->fee($payload, $row),
            'scholarship' => $this->scholarship($payload, $row),
            'accreditation' => $this->accreditation($payload, $row),
            default => throw new RuntimeException('Unsupported financial/accreditation entity type.'),
        };

        $row->update([
            'status' => 'promoted',
            'match_state' => 'enriched_canonical',
            'canonical_subject_type' => $entity::class,
            'canonical_subject_id' => $entity->id,
        ]);

        return $entity;
    }

    private function parent(ImportRow $row): object
    {
        if (! $row->parent_subject_type || ! $row->parent_subject_id) {
            throw new RuntimeException('A canonical parent is required.');
        }

        return match ($row->parent_subject_type) {
            ProgramOffering::class => ProgramOffering::findOrFail($row->parent_subject_id),
            AdmissionIntake::class => AdmissionIntake::findOrFail($row->parent_subject_id),
            default => throw new RuntimeException('Unsupported canonical parent.'),
        };
    }

    private function fee(array $payload, ImportRow $row): FeeRecord
    {
        $parent = $this->parent($row);
        $type = $parent instanceof AdmissionIntake ? 'admission_intake_id' : 'program_offering_id';

        $data = [
            $type => $parent->id,
            'fee_type' => $payload['fee_type'] ?? 'tuition',
            'amount_type' => $payload['amount_type'] ?? 'fixed',
            'amount' => $payload['amount'] ?? null,
            'minimum_amount' => $payload['minimum_amount'] ?? null,
            'maximum_amount' => $payload['maximum_amount'] ?? null,
            'currency' => $payload['currency'] ?? 'PKR',
            'frequency' => $payload['frequency'] ?? null,
            'status' => $payload['status'] ?? 'known',
            'effective_from' => $payload['effective_from'] ?? null,
            'effective_until' => $payload['effective_until'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ];

        if (
            empty($data['amount'])
            && empty($data['minimum_amount'])
            && empty($data['maximum_amount'])
            && empty($payload['notes'])
        ) {
            throw new RuntimeException('Fee record needs a recorded amount/range or explanatory note.');
        }

        return FeeRecord::create($data);
    }

    private function scholarship(array $payload, ImportRow $row): Scholarship
    {
        $parent = $this->parent($row);

        $data = [
            'name' => trim((string) ($payload['name'] ?? '')),
            'scholarship_type' => $payload['scholarship_type'] ?? null,
            'eligibility_basis' => $payload['eligibility_basis'] ?? null,
            'eligibility_description' => $payload['eligibility_description'] ?? null,
            'benefit_type' => $payload['benefit_type'] ?? null,
            'amount_min' => $payload['amount_min'] ?? null,
            'amount_max' => $payload['amount_max'] ?? null,
            'currency' => $payload['currency'] ?? 'PKR',
            'benefit_frequency' => $payload['benefit_frequency'] ?? null,
            'duration' => $payload['duration'] ?? null,
            'renewal_condition' => $payload['renewal_condition'] ?? null,
            'application_open' => $payload['application_open'] ?? null,
            'application_close' => $payload['application_close'] ?? null,
            'data_state' => $payload['data_state'] ?? 'awaiting_verification',
            'status' => $payload['status'] ?? 'candidate',
            'effective_from' => $payload['effective_from'] ?? null,
            'effective_until' => $payload['effective_until'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ];

        if ($data['name'] === '') {
            throw new RuntimeException('Scholarship name is required.');
        }

        if ($parent instanceof AdmissionIntake) {
            $data['admission_intake_id'] = $parent->id;
        } else {
            $data['program_offering_id'] = $parent->id;
        }

        return Scholarship::create($data);
    }

    private function accreditation(array $payload, ImportRow $row): Accreditation
    {
        $parent = $this->parent($row);

        $data = [
            'name' => trim((string) ($payload['name'] ?? '')),
            'recognition_type' => $payload['recognition_type'] ?? null,
            'authority_name' => $payload['authority_name'] ?? null,
            'scope_type' => $payload['scope_type'] ?? null,
            'reference_identifier' => $payload['reference_identifier'] ?? null,
            'status' => $payload['status'] ?? 'under_review',
            'effective_from' => $payload['effective_from'] ?? null,
            'effective_until' => $payload['effective_until'] ?? null,
            'last_verified_at' => $payload['last_verified_at'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ];

        if ($data['name'] === '') {
            throw new RuntimeException('Accreditation/recognition name is required.');
        }

        if ($parent instanceof ProgramOffering) {
            $data['program_offering_id'] = $parent->id;
        } else {
            $data['institution_id'] = $parent->id;
        }

        return Accreditation::create($data);
    }
}
