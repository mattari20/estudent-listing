<?php

namespace Tests\Feature;

use App\Services\EnrichmentImportSchemaService;
use Tests\TestCase;

class EnrichmentImportWhitespaceNormalizationTest extends TestCase
{
    public function test_import_normalization_collapses_repeated_whitespace_in_name_and_address(): void
    {
        $normalized = app(EnrichmentImportSchemaService::class)->normalize([
            'name' => "  Main   City\tCampus  ",
            'slug' => '',
            'address' => "  Road   12\nBuilding A ",
        ], 'campus');

        $this->assertSame('Main City Campus', $normalized['name']);
        $this->assertSame('main-city-campus', $normalized['slug']);
        $this->assertSame('Road 12 Building A', $normalized['address']);
    }
}
