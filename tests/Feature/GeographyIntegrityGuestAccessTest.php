<?php

namespace Tests\\Feature;

use Tests\\TestCase;

class GeographyIntegrityGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_before_geography_integrity_is_accessed(): void
    {
        $this->get('/institutes/operations/integrity')->assertRedirect();
    }
}
