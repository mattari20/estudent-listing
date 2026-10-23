<?php

namespace Tests\Feature;

use App\Services\StructuredEnrichmentValidator;
use Tests\TestCase;

class OfferingDeliveryModeNormalizationTest extends TestCase
{
    public function test_delivery_mode_whitespace_is_trimmed_and_valid_campus_is_preserved(): void
    {
        $normalized = app(StructuredEnrichmentValidator::class)->normalize('offering', [
            'program_id' => 10,
            'campus_id' => 20,
            'delivery_mode' => ' on_campus ',
        ]);

        $this->assertSame('on_campus', $normalized['delivery_mode']);
        $this->assertSame(20, $normalized['campus_id']);
    }
}
