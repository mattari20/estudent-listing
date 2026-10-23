<?php

namespace Tests\Feature;

use App\Services\StructuredEnrichmentValidator;
use Tests\TestCase;

class OnCampusOfferingCampusValidationTest extends TestCase
{
    public function test_on_campus_offering_with_campus_id_keeps_campus_relationship(): void
    {
        $normalized = app(StructuredEnrichmentValidator::class)->normalize('offering', [
            'program_id' => 10,
            'campus_id' => 20,
            'delivery_mode' => 'on_campus',
        ]);

        $this->assertSame(10, $normalized['program_id']);
        $this->assertSame(20, $normalized['campus_id']);
        $this->assertSame('on_campus', $normalized['delivery_mode']);
    }
}
