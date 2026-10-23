<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\EnrichmentImportRow;
use App\Models\ImportBatch;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Services\StructuredEnrichmentPromoter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class UnsupportedEnrichmentPromotionSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsupported_approved_entity_fails_without_creating_canonical_records_or_changing_row_status(): void
    {
        $batch = ImportBatch::create([
            'batch_key' => uniqid('unsupported-', true),
            'entity_type' => 'enrichment',
            'source_type' => 'primary',
            'status' => 'validated',
        ]);

        $row = EnrichmentImportRow::create([
            'import_batch_id' => $batch->id,
            'entity_type' => 'institution',
            'status' => 'approved',
            'match_state' => 'matched',
            'payload' => ['name' => 'Unsupported synthetic entity'],
            'normalized_payload' => ['name' => 'Unsupported synthetic entity'],
        ]);

        $before = [
            'institutions' => Institution::count(),
            'campuses' => Campus::count(),
            'programs' => Program::count(),
            'program_offerings' => ProgramOffering::count(),
        ];

        try {
            app(StructuredEnrichmentPromoter::class)->promote($row);
            $this->fail('Unsupported enrichment entity types must not be promoted.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Unsupported enrichment entity type.', $exception->getMessage());
        }

        $this->assertSame('approved', $row->fresh()->status);
        $this->assertSame($before['institutions'], Institution::count());
        $this->assertSame($before['campuses'], Campus::count());
        $this->assertSame($before['programs'], Program::count());
        $this->assertSame($before['program_offerings'], ProgramOffering::count());
    }
}
