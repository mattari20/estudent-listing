<?php

namespace Tests\Feature;

use App\Models\ImportSchemaProfile;
use App\Services\EnrichmentImportSchemaService;
use App\Services\GeographySchemaProfileService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CsvHeaderValidationTest extends TestCase
{
    #[Test]
    public function enrichment_import_rejects_duplicate_column_names(): void
    {
        $errors = app(EnrichmentImportSchemaService::class)->validateHeaders(
            ['name', 'slug', 'name'],
            'campus',
        );

        $this->assertContains('Duplicate column names are not allowed.', $errors);
    }

    #[Test]
    public function geography_import_rejects_duplicate_column_names(): void
    {
        $profile = new ImportSchemaProfile([
            'profile_key' => 'geography-csv',
            'version' => '1.0',
            'entity_type' => 'geography',
            'columns' => ['name', 'slug', 'type', 'parent_slug', 'parent_type'],
            'required_columns' => ['name', 'slug', 'type', 'parent_slug', 'parent_type'],
        ]);

        $errors = app(GeographySchemaProfileService::class)->validateHeaders(
            ['name', 'slug', 'type', 'parent_slug', 'parent_type', 'name'],
            $profile,
        );

        $this->assertContains('Duplicate column names are not allowed.', $errors);
    }
}
