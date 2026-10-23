<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstituteForgotPasswordFormTest extends TestCase
{
    public function test_forgot_password_form_is_accessible(): void
    {
        $this->get('/institutes/forgot-password')
            ->assertOk()
            ->assertSee('email');
    }
}
