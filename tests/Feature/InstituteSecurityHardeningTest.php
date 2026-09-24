<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class InstituteSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_operator_cannot_remain_authenticated_after_login(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'password' => 'password',
            'role' => 'user',
        ]);

        $response = $this->post(route('institutes.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_repeated_invalid_logins_are_throttled(): void
    {
        $key = 'regular@example.com|127.0.0.1';
        RateLimiter::clear($key);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('institutes.login.store'), [
                'email' => 'regular@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post(route('institutes.login.store'), [
            'email' => 'regular@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }
}
