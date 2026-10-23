<?php

namespace Tests\Feature;

use App\Services\EnrichmentImportSchemaService;
use Tests\TestCase;

class EnrichmentImportDuplicateSlugValidationTest extends TestCase
{
    public function test_duplicate_slug_in_same_batch_is_reported(): void
    {
        $errors = app(EnrichmentImportSchemaService::class)->validateRow([
            'name' => 'Synthetic Campus',
            'slug' => 'shared-campus-slug',
        ], 'campus', ['shared-campus-slug' => true]);

        $this->assertContains('duplicate slug within import batch', $errors);
    }

    public function test_unique_slug_is_not_flagged_as_batch_duplicate(): void
    {
        $errors = app(EnrichmentImportSchemaService::class)->validateRow([
            'name' => 'Another Synthetic Campus',
            'slug' => 'another-synthetic-campus',
        ], 'campus', ['shared-campus-slug' => true]);

        $this->assertNotContains('duplicate slug within import batch', $errors);
    }
}
