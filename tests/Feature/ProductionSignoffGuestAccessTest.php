<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionSignoffGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_from_production_signoff(): void
    {
        $this->get('/institutes/operations/production-signoff')->assertRedirect();
    }
}
