<?php

namespace Tests\\Feature;

use Tests\\TestCase;

class ImportVerificationGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_before_import_verification_is_accessed(): void
    {
        $this->get('/institutes/operations/imports/1/verification')->assertRedirect();
    }
}
