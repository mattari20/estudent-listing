<?php

namespace Tests\Feature;

use Tests\TestCase;

class SourceEvidenceCaptureGuestAccessTest extends TestCase
{
    public function test_guest_is_redirected_before_source_evidence_is_submitted(): void
    {
        $this->post('/institutes/operations/sources/1/evidence', [])->assertRedirect();
    }
}
