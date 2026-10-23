<?php

namespace Tests\Feature;

use App\Services\StructuredEnrichmentValidator;
use InvalidArgumentException;
use Tests\TestCase;

class CampuslessOnlineOfferingValidationTest extends TestCase
{
    public function test_online_offering_can_be_normalized_without_a_campus(): void
    {
        $normalized = app(StructuredEnrichmentValidator::class)->normalize('offering', [
            'program_id' => 42,
            'delivery_mode' => 'online',
        ]);

        $this->assertSame(42, $normalized['program_id']);
        $this->assertNull($normalized['campus_id']);
    }

    public function test_on_campus_offering_still_requires_campus_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Offering requires campus_id unless delivery_mode is online.');

        app(StructuredEnrichmentValidator::class)->normalize('offering', [
            'program_id' => 42,
            'delivery_mode' => 'on_campus',
        ]);
    }
}
