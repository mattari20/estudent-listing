<?php

namespace Tests\\Feature;

use Tests\\TestCase;

class ImportPromotionReadinessGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_before_promotion_readiness_is_accessed(): void
    {
        $this->get('/institutes/operations/imports/1/promotion-readiness')->assertRedirect();
    }
}
