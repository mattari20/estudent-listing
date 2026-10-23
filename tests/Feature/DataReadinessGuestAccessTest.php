<?php

namespace Tests\Feature;

use Tests\TestCase;

class DataReadinessGuestAccessTest extends TestCase
{
    public function test_guest_cannot_access_data_readiness_dashboard(): void
    {
        $this->get('/institutes/operations/data-readiness')->assertRedirect();
    }
}
