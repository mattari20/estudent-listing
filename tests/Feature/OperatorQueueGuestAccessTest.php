<?php

namespace Tests\Feature;

use Tests\TestCase;

class OperatorQueueGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_from_operator_queue(): void
    {
        $this->get('/institutes/operations')->assertRedirect();
    }
}
