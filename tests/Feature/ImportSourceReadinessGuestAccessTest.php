<?php

namespace Tests\\Feature;

use Tests\\TestCase;

class ImportSourceReadinessGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_before_source_readiness_is_accessed(): void
    {
        $this->get('/institutes/operations/imports/1/source-readiness')->assertRedirect();
    }
}
