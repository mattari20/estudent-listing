<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstituteAdminAssistedRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reset_another_admin_and_password_change_is_required(): void
    {
        $admin = User::create(['name' => 'Admin One', 'email' => 'admin1@example.test', 'password' => 'Old!StrongPassphrase2026', 'role' => 'admin']);
        $target = User::create(['name' => 'Admin Two', 'email' => 'admin2@example.test', 'password' => 'Old!StrongPassphrase2026', 'role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('institutes.operations.users.reset-password', $target));

        $response->assertRedirect(route('institutes.operations.users.index')
            )->assertSessionHas('temporary_password_once')
            ->assertSessionHas('temporary_password_user', 'admin2@example.test');

        $target->refresh();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'must_change_password' => true]);
        $this->assertFalse(Hash::check('Old!StrongPassphrase2026', $target->password));
    }

    public function test_admin_cannot_reset_own_password_from_user_management(): void
    {
        $admin = User::create(['name' => 'Admin One', 'email' => 'admin1@example.test', 'password' => 'Old!StrongPassphrase2026', 'role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('institutes.operations.users.reset-password', $admin))
            ->assertStatus(422);
    }

    public function test_operator_cannot_reset_another_account(): void
    {
        $operator = User::create(['name' => 'Operator', 'email' => 'operator@example.test', 'password' => 'Old!StrongPassphrase2026', 'role' => 'operator']);
        $target = User::create(['name' => 'Other Operator', 'email' => 'other@example.test', 'password' => 'Old!StrongPassphrase2026', 'role' => 'operator']);

        $this->actingAs($operator)
            ->post(route('institutes.operations.users.reset-password', $target))
            ->assertRedirect(route('institutes.login'));
    }
}
