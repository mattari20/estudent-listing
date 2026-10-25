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
        private readonly ?InstituteDiscoveryPromotionGuard $promotionGuard = null,
        private readonly ?StructuredEnrichmentPayloadValidator $payloadValidator = null
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

            if (! in_array($lockedRow->entity_type, ['campus', 'program', 'offering'], true)) {
                throw new RuntimeException('Unsupported enrichment entity type.');
            }

            $validator = $this->payloadValidator ?? app(StructuredEnrichmentPayloadValidator::class);
            $payloadBlockers = $validator->blockers($lockedRow->entity_type, $payload);
            if ($payloadBlockers !== []) {
                throw new RuntimeException('Enrichment payload blocked: '.implode(',', $payloadBlockers));
            }

            $beforeSnapshot = $this->currentCanonicalSnapshot($lockedRow->entity_type, $payload);

            // Discovery-sourced rows must carry the governed review contract.
            // Legacy/manual import rows remain compatible until they are migrated.
            $isDiscoverySourced = isset($payload['_provenance']) || isset($payload['_governed_review']);
            if ($isDiscoverySourced) {
                $review = $payload['_governed_review'] ?? null;
                if (! is_array($review)) {
                    throw new RuntimeException('Governed discovery proposal is missing its review contract.');
                }

                $guard = $this->promotionGuard ?? app(InstituteDiscoveryPromotionGuard::class);
                $decision = $guard->evaluate($review, $beforeSnapshot);
                if (! $decision['eligible']) {
                    throw new RuntimeException(
                        'Governed discovery promotion blocked: '.implode(',', $decision['blockers'])
                    );
                }
            }

            // Internal metadata is evidence, not canonical model attributes.
            unset($payload['_provenance'], $payload['_governed_review']);

            $canonical = match ($lockedRow->entity_type) {
                'campus' => $this->promoteCampus($payload),
                'program' => $this->promoteProgram($payload),
                'offering' => $this->promoteOffering($payload),
                default => throw new RuntimeException('Unsupported enrichment entity type.'),
            };

            $lockedRow->loadMissing('batch');
            StructuredEnrichmentPromotionEvent::create([
                'enrichment_import_row_id' => $lockedRow->id,
                'import_batch_id' => $lockedRow->import_batch_id,
                'entity_type' => $lockedRow->entity_type,
                'event_type' => 'promotion_succeeded',
                'canonical_type' => $canonical::class,
                'canonical_id' => $canonical->getKey(),
                'actor_type' => 'system',
                'reason' => $isDiscoverySourced ? 'Governed discovery promotion passed policy and snapshot checks.' : 'Approved legacy/manual enrichment row promoted.',
                'before_snapshot' => $beforeSnapshot,
                'after_snapshot' => $canonical->getAttributes(),
                'metadata' => [
                    'governed_discovery' => $isDiscoverySourced,
                    'review_package_id' => $isDiscoverySourced ? ($review['review_package_id'] ?? null) : null,
                ],
                'occurred_at' => now(),
            ]);

            return $canonical;
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
