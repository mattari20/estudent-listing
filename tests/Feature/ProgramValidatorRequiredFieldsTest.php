<?php

namespace Tests\Feature;

use App\Services\StructuredEnrichmentValidator;
use InvalidArgumentException;
use Tests\TestCase;

class ProgramValidatorRequiredFieldsTest extends TestCase
{
    public function test_program_without_institution_id_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Program requires institution_id and name.');

        app(StructuredEnrichmentValidator::class)->normalize('program', [
            'name' => 'Synthetic Program',
        ]);
    }

    public function test_program_without_name_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Program requires institution_id and name.');

        app(StructuredEnrichmentValidator::class)->normalize('program', [
            'institution_id' => 1,
            'name' => '   ',
        ]);
    }
}
