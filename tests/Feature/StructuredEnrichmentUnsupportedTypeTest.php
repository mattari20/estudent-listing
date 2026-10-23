<?php

namespace Tests\Feature;

use App\Services\StructuredEnrichmentValidator;
use InvalidArgumentException;
use Tests\TestCase;

class StructuredEnrichmentUnsupportedTypeTest extends TestCase
{
    public function test_unknown_entity_type_is_rejected_before_normalization(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported enrichment entity type.');

        app(StructuredEnrichmentValidator::class)->normalize('unknown-type', [
            'name' => 'Synthetic unknown entity',
        ]);
    }
}
