<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\EnrichmentImportRow;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StructuredEnrichmentPromoter
{
    public function __construct(
        private readonly InstituteDiscoveryPromotionGuard $promotionGuard
    ) {
    }

    public function promote(EnrichmentImportRow $row): object
    {
        if ($row->status !== 'approved') {
            throw new RuntimeException('Only approved enrichment rows may be promoted.');
        }

        return DB::transaction(function () use ($row): object {
            $lockedRow = EnrichmentImportRow::query()->lockForUpdate()->findOrFail($row->getKey());

            if ($lockedRow->status !== 'approved') {
                throw new RuntimeException('Enrichment row approval changed before promotion.');
            }

            $payload = $lockedRow->normalized_payload ?? $lockedRow->payload ?? [];
            if (! is_array($payload) || $payload === []) {
                throw new RuntimeException('Enrichment row has no usable payload.');
            }

            // Discovery-sourced rows must carry the governed review contract.
            // Legacy/manual import rows remain compatible until they are migrated.
            $isDiscoverySourced = isset($payload['_provenance']) || isset($payload['_governed_review']);
            if ($isDiscoverySourced) {
                $review = $payload['_governed_review'] ?? null;
                if (! is_array($review)) {
                    throw new RuntimeException('Governed discovery proposal is missing its review contract.');
                }

                $current = $this->currentCanonicalSnapshot($lockedRow->entity_type, $payload);
                $decision = $this->promotionGuard->evaluate($review, $current);
                if (! $decision['eligible']) {
                    throw new RuntimeException(
                        'Governed discovery promotion blocked: '.implode(',', $decision['blockers'])
                    );
                }
            }

            // Internal metadata is evidence, not canonical model attributes.
            unset($payload['_provenance'], $payload['_governed_review']);

            return match ($lockedRow->entity_type) {
                'campus' => $this->promoteCampus($payload),
                'program' => $this->promoteProgram($payload),
                'offering' => $this->promoteOffering($payload),
                default => throw new RuntimeException('Unsupported enrichment entity type.'),
            };
        }, 3);
    }

    private function currentCanonicalSnapshot(string $entityType, array $payload): array
    {
        $record = match ($entityType) {
            'campus' => Campus::query()
                ->where('institution_id', $payload['institution_id'] ?? null)
                ->where('slug', $payload['slug'] ?? null)
                ->lockForUpdate()
                ->first(),
            'program' => Program::query()
                ->where('institution_id', $payload['institution_id'] ?? null)
                ->where('slug', $payload['slug'] ?? null)
                ->lockForUpdate()
                ->first(),
            'offering' => ProgramOffering::query()
                ->where('program_id', $payload['program_id'] ?? null)
                ->where('campus_id', $payload['campus_id'] ?? null)
                ->where('delivery_mode', $payload['delivery_mode'] ?? null)
                ->where('intake_label', $payload['intake_label'] ?? null)
                ->lockForUpdate()
                ->first(),
            default => throw new RuntimeException('Unsupported enrichment entity type.'),
        };

        return $record?->getAttributes() ?? [];
    }

    private function promoteCampus(array $payload): Campus
    {
        return Campus::updateOrCreate(
            ['institution_id' => $payload['institution_id'], 'slug' => $payload['slug']],
            $this->campus($payload)
        );
    }

    private function promoteProgram(array $payload): Program
    {
        return Program::updateOrCreate(
            ['institution_id' => $payload['institution_id'], 'slug' => $payload['slug']],
            $this->program($payload)
        );
    }

    private function promoteOffering(array $payload): ProgramOffering
    {
        return ProgramOffering::updateOrCreate(
            [
                'program_id' => $payload['program_id'],
                'campus_id' => $payload['campus_id'],
                'delivery_mode' => $payload['delivery_mode'],
                'intake_label' => $payload['intake_label'] ?? null,
            ],
            $this->offering($payload)
        );
    }

    private function campus(array $payload): array
    {
        return array_intersect_key($payload, array_flip([
            'institution_id', 'geography_id', 'name', 'slug', 'campus_type',
            'status', 'address', 'postal_code', 'latitude', 'longitude',
        ]));
    }

    private function program(array $payload): array
    {
        return array_intersect_key($payload, array_flip([
            'institution_id', 'name', 'slug', 'program_level', 'award',
            'discipline', 'status', 'description',
        ]));
    }

    private function offering(array $payload): array
    {
        return array_intersect_key($payload, array_flip([
            'program_id', 'campus_id', 'delivery_mode', 'status', 'intake_label',
            'duration', 'availability', 'effective_from', 'effective_until',
        ]));
    }
}
