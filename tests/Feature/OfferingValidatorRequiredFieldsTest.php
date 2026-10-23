<?php

namespace Tests\Feature;

use App\Services\StructuredEnrichmentValidator;
use InvalidArgumentException;
use Tests\TestCase;

class OfferingValidatorRequiredFieldsTest extends TestCase
{
    public function test_offering_without_program_id_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Offering requires program_id.');

        app(StructuredEnrichmentValidator::class)->normalize('offering', [
            'campus_id' => 3,
            'delivery_mode' => 'on_campus',
        ]);
    }

    public function test_offering_without_delivery_mode_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Offering requires delivery_mode.');

        app(StructuredEnrichmentValidator::class)->normalize('offering', [
            'program_id' => 3,
            'campus_id' => 4,
        ]);
    }
}
