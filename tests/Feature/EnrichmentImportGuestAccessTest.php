<?php

namespace Tests\Feature;

use Tests\TestCase;

class EnrichmentImportGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_from_enrichment_import_template(): void
    {
        $this->get('/institutes/operations/enrichment-import/template')->assertRedirect();
    }
}
