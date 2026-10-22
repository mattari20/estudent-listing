<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteDataReadinessAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_access_data_readiness_operations(): void
    {
        $viewer = User::create([
            'name' => 'Readiness Viewer',
            'email' => 'readiness-viewer@example.test',
            'password' => 'test-password',
            'role' => 'viewer',
        ]);

        $this->actingAs($viewer)
            ->get(route('institutes.operations.data-readiness'))
            ->assertRedirect(route('institutes.login'));
    }
}
