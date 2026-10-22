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

    public function test_operator_can_access_geography_integrity_review(): void
    {
        $operator = User::create([
            'name' => 'Integrity Operator',
            'email' => 'integrity-operator@example.test',
            'password' => 'test-password',
            'role' => 'operator',
        ]);

        $this->actingAs($operator)
            ->get(route('institutes.operations.integrity'))
            ->assertOk();
    }

    public function test_admin_can_access_geography_integrity_review(): void
    {
        $user = User::create([
            'name' => 'Integrity Admin',
            'email' => 'integrity-admin@example.test',
            'password' => 'test-password',
            'role' => 'admin',
        ]);

        $this->actingAs($user)
            ->get(route('institutes.operations.integrity'))
            ->assertOk();
    }

    public function test_master_admin_can_access_geography_integrity_review(): void
    {
        $user = User::create([
            'name' => 'Integrity Master Admin',
            'email' => 'integrity-master-admin@example.test',
            'password' => 'test-password',
            'role' => 'master_admin',
        ]);

        $this->actingAs($user)
            ->get(route('institutes.operations.integrity'))
            ->assertOk();
    }
}
