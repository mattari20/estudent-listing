<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\InstituteResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class InstitutePasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_forgot_password_form_is_available_without_sign_in(): void
    {
        $this->get(route('institutes.password.request'))
            ->assertOk()
            ->assertSee('Forgot your password?')
            ->assertSee('Email reset link');
    }

    public function test_forgot_password_requests_a_reset_link_for_an_authorized_account(): void
    {
        User::create([
            'name' => 'Operator',
            'email' => 'operator@example.test',
            'password' => 'Old!StrongPassphrase2026',
            'role' => 'operator',
        ]);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'operator@example.test'])
            ->andReturn(Password::RESET_LINK_SENT);

        $this->post(route('institutes.password.email'), [
            'email' => 'operator@example.test',
        ])->assertRedirect()
          ->assertSessionHas('status');
    }

    public function test_forgot_password_response_does_not_disclose_unknown_email(): void
    {
        Notification::fake();

        $this->post(route('institutes.password.email'), [
            'email' => 'unknown@example.test',
        ])->assertRedirect()
          ->assertSessionHas('status', 'If an authorized account exists for that email address, a password reset link will be sent shortly.');

        Notification::assertNothingSent();
    }

    public function test_valid_reset_token_changes_password_and_rotates_remember_token(): void
    {
        $user = User::create([
            'name' => 'Operator',
            'email' => 'operator@example.test',
            'password' => 'Old!StrongPassphrase2026',
            'role' => 'operator',
            'remember_token' => 'old-remember-token',
        ]);
        $token = Password::createToken($user);

        $this->post(route('institutes.password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'N3w!SecurePassphrase2026',
            'password_confirmation' => 'N3w!SecurePassphrase2026',
        ])->assertRedirect(route('institutes.login'))
          ->assertSessionHas('status');

        $updated = $user->fresh();
        $this->assertTrue(Hash::check('N3w!SecurePassphrase2026', $updated->password));
        $this->assertNotSame('old-remember-token', $updated->remember_token);
    }

    public function test_invalid_reset_token_does_not_change_password(): void
    {
        $user = User::create([
            'name' => 'Operator',
            'email' => 'operator@example.test',
            'password' => 'Old!StrongPassphrase2026',
            'role' => 'operator',
        ]);

        $this->post(route('institutes.password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'N3w!SecurePassphrase2026',
            'password_confirmation' => 'N3w!SecurePassphrase2026',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('Old!StrongPassphrase2026', $user->fresh()->password));
    }
}
