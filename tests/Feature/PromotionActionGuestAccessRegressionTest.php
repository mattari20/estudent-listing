<?php

namespace Tests\Feature;

use Tests\TestCase;

class PromotionActionGuestAccessRegressionTest extends TestCase
{
    public function test_guest_cannot_submit_a_row_promotion_action(): void
    {
        $this->post('/institutes/operations/rows/1/promote', [])->assertRedirect();
    }
}
