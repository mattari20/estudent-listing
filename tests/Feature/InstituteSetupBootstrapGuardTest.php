<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstituteSetupBootstrapGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_admin_prevents_initial_setup_form_from_opening(): void
    {
        User::create([
            'name' => 'Existing Master Admin',
            'email' => 'master-admin@example.test',
            'password' => 'Existing!StrongPassphrase2026',
            'role' => 'master_admin',
        ]);

        $this->get(route('institutes.setup'))
            ->assertRedirect(route('institutes.login'));
    }

    public function test_master_admin_prevents_initial_setup_submission_from_creating_another_admin(): void
    {
        User::create([
            'name' => 'Existing Master Admin',
            'email' => 'master-admin@example.test',
            'password' => 'Existing!StrongPassphrase2026',
            'role' => 'master_admin',
        ]);

        $this->post(route('institutes.setup.store'), [
            'name' => 'Unexpected Second Admin',
            'email' => 'second-admin@example.test',
            'password' => 'N3w!SecurePassphrase2026',
            'password_confirmation' => 'N3w!SecurePassphrase2026',
        ])->assertRedirect(route('institutes.login'));

        $this->assertDatabaseMissing('users', ['email' => 'second-admin@example.test']);
        $this->assertDatabaseCount('users', 1);
    }
}
