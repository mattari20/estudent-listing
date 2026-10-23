<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstitutePasswordRecoveryValidationTest extends TestCase
{
    public function test_password_recovery_rejects_invalid_email_input(): void
    {
        $this->from('/institutes/forgot-password')
            ->post('/institutes/forgot-password', ['email' => 'not-an-email'])
            ->assertRedirect('/institutes/forgot-password')
            ->assertSessionHasErrors('email');
    }
}
