<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstitutionImportGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_from_institution_import_template(): void
    {
        $this->get('/institutes/operations/import/template')->assertRedirect();
    }
}
