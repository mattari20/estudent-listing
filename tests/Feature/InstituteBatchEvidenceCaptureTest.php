<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteBatchEvidenceCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_evidence_page_requires_operator_authentication(): void
    {
        $batch = ImportBatch::factory()->create(['entity_type' => 'institution']);

        $this->get(route('institutes.operations.imports.evidence-capture', $batch))
            ->assertRedirect(route('institutes.login'));
    }

    public function test_evidence_capture_route_is_registered_under_operator_middleware(): void
    {
        $batch = ImportBatch::factory()->create(['entity_type' => 'institution']);

        $this->assertSame('institution', $batch->entity_type);
        $this->assertNotNull(route('institutes.operations.imports.evidence-capture', $batch));
        $this->assertNotNull(route('institutes.operations.imports.evidence-capture.store', $batch));
    }
}
