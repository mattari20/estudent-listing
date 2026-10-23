<?php

namespace Tests\Feature;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class PasswordRecoveryFormReachabilityTest extends TestCase
{
    public function test_password_recovery_form_renders_for_guests(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->get('/institutes/forgot-password')->assertOk();
    }
}
