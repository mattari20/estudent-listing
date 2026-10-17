<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstituteAccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_open_password_change_form(): void
    {
        $operator = User::create([
            'name' => 'Operator',
            'email' => 'operator@example.test',
            'password' => 'Old!StrongPassphrase2026',
            'role' => 'operator',
        ]);

        $this->actingAs($operator)
            ->get(route('institutes.operations.account.password.edit'))
            ->assertOk()
            ->assertSee('Current password')
            ->assertSee('Confirm new password');
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'Old!StrongPassphrase2026',
            'role' => 'admin',
        ]);
        $user->forceFill(['remember_token' => 'old-remember-token'])->save();

        $this->actingAs($user)
            ->put(route('institutes.operations.account.password.update'), [
                'current_password' => 'incorrect-current-password',
                'password' => 'N3w!SecurePassphrase2026',
                'password_confirmation' => 'N3w!SecurePassphrase2026',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('Old!StrongPassphrase2026', $user->fresh()->password));
        $this->assertSame('old-remember-token', $user->fresh()->remember_token);
    }

    public function test_password_change_updates_hash_and_rotates_remember_token(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'Old!StrongPassphrase2026',
            'role' => 'admin',
            'remember_token' => 'old-remember-token',
        ]);

        $this->actingAs($user)
            ->put(route('institutes.operations.account.password.update'), [
                'current_password' => 'Old!StrongPassphrase2026',
                'password' => 'N3w!SecurePassphrase2026',
                'password_confirmation' => 'N3w!SecurePassphrase2026',
            ])
            ->assertRedirect(route('institutes.operations.account.password.edit'))
            ->assertSessionHas('status');

        $updated = $user->fresh();
        $this->assertTrue(Hash::check('N3w!SecurePassphrase2026', $updated->password));
        $this->assertNotSame('old-remember-token', $updated->remember_token);
        $this->assertSame(60, strlen($updated->remember_token));
    }

    public function test_password_change_rejects_weak_passwords(): void
    {
        $user = User::create([
            'name' => 'Operator',
            'email' => 'operator@example.test',
            'password' => 'Old!StrongPassphrase2026',
            'role' => 'operator',
        ]);

        $this->actingAs($user)
            ->put(route('institutes.operations.account.password.update'), [
                'current_password' => 'Old!StrongPassphrase2026',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('Old!StrongPassphrase2026', $user->fresh()->password));
    }

    public function test_admin_can_create_an_operator_account(): void
    {
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'administrator@example.test',
            'password' => 'Admin!StrongPassphrase2026',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->post(route('institutes.operations.users.store'), [
                'name' => 'New Operator',
                'email' => 'new.operator@example.test',
                'role' => 'operator',
                'password' => 'N3w!SecurePassphrase2026',
                'password_confirmation' => 'N3w!SecurePassphrase2026',
            ])
            ->assertRedirect(route('institutes.operations.users.index'))
            ->assertSessionHas('status');

        $created = User::where('email', 'new.operator@example.test')->first();
        $this->assertNotNull($created);
        $this->assertSame('operator', $created->role);
        $this->assertTrue(Hash::check('N3w!SecurePassphrase2026', $created->password));
    }

    public function test_operator_cannot_create_accounts(): void
    {
        $operator = User::create([
            'name' => 'Operator',
            'email' => 'operator@example.test',
            'password' => 'Operator!StrongPassphrase2026',
            'role' => 'operator',
        ]);

        $this->actingAs($operator)
            ->get(route('institutes.operations.users.create'))
            ->assertRedirect(route('institutes.login'));
    }
}
