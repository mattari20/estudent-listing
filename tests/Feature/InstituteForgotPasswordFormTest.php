<?php

namespace Tests\Feature;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class InstituteForgotPasswordFormTest extends TestCase
{
    public function test_forgot_password_form_is_accessible(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class)
            ->get('/institutes/forgot-password')
            ->assertOk()
            ->assertSee('email');
    }
}
