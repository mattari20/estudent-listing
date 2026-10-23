<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReadinessDashboardGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_from_operator_readiness_dashboard(): void
    {
        $this->get('/institutes/operations/readiness-dashboard')->assertRedirect();
    }
}
