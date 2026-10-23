<?php

namespace Tests\Feature;

use App\Services\StructuredEnrichmentValidator;
use InvalidArgumentException;
use Tests\TestCase;

class CampusValidatorRequiredFieldsTest extends TestCase
{
    public function test_campus_without_institution_id_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Campus requires institution_id and name.');

        app(StructuredEnrichmentValidator::class)->normalize('campus', [
            'name' => 'Synthetic Campus',
        ]);
    }

    public function test_campus_without_name_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Campus requires institution_id and name.');

        app(StructuredEnrichmentValidator::class)->normalize('campus', [
            'institution_id' => 1,
            'name' => '   ',
        ]);
    }
}
