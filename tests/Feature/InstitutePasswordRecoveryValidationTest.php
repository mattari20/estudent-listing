<?php

namespace Tests\Feature;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class InstitutePasswordRecoveryValidationTest extends TestCase
{
    public function test_password_recovery_rejects_invalid_email_input(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class)
            ->from('/institutes/forgot-password')
            ->post('/institutes/forgot-password', ['email' => 'not-an-email'])
            ->assertRedirect('/institutes/forgot-password')
            ->assertSessionHasErrors('email');
    }
}
