<?php

namespace Tests\Feature;

use Tests\TestCase;

class GeographyIntegrityReviewAccessTest extends TestCase
{
    public function test_public_cannot_access_geography_integrity_review(): void
    {
        $this->get(route('institutes.operations.integrity'))
            ->assertRedirect();
    }
}
