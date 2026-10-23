<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstitutionImportTemplateGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_before_operator_page_is_accessed(): void
    {
        $this->get('/institutes/operations/import/template')->assertRedirect();
    }
}
