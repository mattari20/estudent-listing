<?php

namespace Tests\Feature;

use App\Services\EnrichmentImportSchemaService;
use Tests\TestCase;

class EnrichmentImportGeographyFieldValidationTest extends TestCase
{
    public function test_campus_row_rejects_non_numeric_coordinates(): void
    {
        $errors = app(EnrichmentImportSchemaService::class)->validateRow([
            'name' => 'Synthetic Campus',
            'slug' => 'synthetic-campus',
            'latitude' => 'north',
            'longitude' => 'west',
            'geography_id' => '',
        ], 'campus', []);

        $this->assertContains('latitude must be numeric', $errors);
        $this->assertContains('longitude must be numeric', $errors);
    }

    public function test_campus_row_rejects_non_positive_geography_id(): void
    {
        $errors = app(EnrichmentImportSchemaService::class)->validateRow([
            'name' => 'Synthetic Campus',
            'slug' => 'synthetic-campus',
            'latitude' => '',
            'longitude' => '',
            'geography_id' => '0',
        ], 'campus', []);

        $this->assertContains('geography_id must be a positive integer', $errors);
    }
}
