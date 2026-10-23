<?php

namespace Tests\Feature;

use Tests\TestCase;

class MonitoringDashboardGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_from_monitoring_dashboard(): void
    {
        $this->get('/institutes/operations/monitoring')->assertRedirect();
    }
}
