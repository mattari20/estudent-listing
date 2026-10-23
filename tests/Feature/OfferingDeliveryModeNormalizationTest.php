<?php

namespace Tests\Feature;

use App\Services\StructuredEnrichmentValidator;
use Tests\TestCase;

class OfferingDeliveryModeNormalizationTest extends TestCase
{
    public function test_delivery_mode_is_trimmed_before_campus_requirement_is_evaluated(): void
    {
        $normalized = app(StructuredEnrichmentValidator::class)->normalize('offering', [
            'program_id' => 10,
            'delivery_mode' => ' online ',
        ]);

        $this->assertSame('online', $normalized['delivery_mode']);
        $this->assertNull($normalized['campus_id']);
    }
}
