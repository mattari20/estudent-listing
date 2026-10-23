<?php

namespace Tests\Feature;

use App\Services\EnrichmentImportSchemaService;
use Tests\TestCase;

class EnrichmentImportSchemaHeaderValidationTest extends TestCase
{
    public function test_unknown_columns_are_rejected_instead_of_silently_accepted(): void
    {
        $errors = app(EnrichmentImportSchemaService::class)->validateHeaders(
            ['name', 'slug', 'unexpected_status_override'],
            'campus',
        );

        $this->assertContains('Unsupported column: unexpected_status_override', $errors);
    }

    public function test_required_name_column_must_be_present(): void
    {
        $errors = app(EnrichmentImportSchemaService::class)->validateHeaders(
            ['slug', 'address'],
            'campus',
        );

        $this->assertContains('Required column missing: name', $errors);
    }
}
