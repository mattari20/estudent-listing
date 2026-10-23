<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicInstitutesLandingTest extends TestCase
{
    public function test_public_institutes_landing_page_is_accessible(): void
    {
        $this->get('/institutes')->assertOk();
    }
}
