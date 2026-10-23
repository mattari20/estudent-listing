<?php

namespace Tests\Feature;

use Tests\TestCase;

class GeographyImportGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_from_geography_import_template(): void
    {
        $this->get('/institutes/operations/geography-import/template')->assertRedirect();
    }
}
