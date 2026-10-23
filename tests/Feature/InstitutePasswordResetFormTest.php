<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstitutePasswordResetFormTest extends TestCase
{
    public function test_reset_form_renders_for_supplied_token(): void
    {
        $this->get('/institutes/reset-password/example-token?email=operator%40example.test')
            ->assertOk()
            ->assertSee('example-token')
            ->assertSee('operator@example.test');
    }
}
