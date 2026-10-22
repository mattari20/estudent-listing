<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyIntegrityReviewAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_cannot_access_geography_integrity_review(): void
    {
        $this->get(route('institutes.operations.integrity'))
            ->assertRedirect();
    }

    public function test_authenticated_viewer_cannot_access_geography_integrity_review(): void
    {
        $viewer = User::create([
            'name' => 'Integrity Viewer',
            'email' => 'integrity-viewer@example.test',
            'password' => 'test-password',
            'role' => 'viewer',
        ]);

        $this->actingAs($viewer)
            ->get(route('institutes.operations.integrity'))
            ->assertRedirect(route('institutes.login'));
    }
}
