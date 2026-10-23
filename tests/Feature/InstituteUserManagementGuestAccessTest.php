<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstituteUserManagementGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_from_user_management(): void
    {
        $this->get('/institutes/operations/users')->assertRedirect();
    }
}
