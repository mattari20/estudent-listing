<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class InstituteUserController
{
    public function index(): View
    {
        $users = User::query()->orderBy('name')->paginate(25);

        return view('institutes.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('institutes.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,operator'],
            'password' => ['required', 'string', Password::min(12)->letters()->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => $validated['password'],
        ]);

        return redirect()
            ->route('institutes.operations.users.index')
            ->with('status', 'User account created successfully.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_if((int) $request->user()->id === (int) $user->id, 422, 'Use Change Password for your own account.');
        abort_if($user->role === 'admin' && User::query()->where('role', 'admin')->count() < 2, 422, 'The last administrator cannot be reset through this action.');

        $temporaryPassword = Str::password(24, symbols: true);
        $user->forceFill([
            'password' => $temporaryPassword,
            'remember_token' => Str::random(60),
            'must_change_password' => true,
        ])->save();

        SecurityAuditEvent::create([
            'event_type' => 'account.password.admin_reset',
            'actor_type' => User::class,
            'actor_id' => $request->user()->id,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'severity' => 'high',
            'description' => 'Administrator reset password; temporary password displayed once and password change required.',
            'request_id' => (string) Str::uuid(),
        ]);

        $request->session()->regenerate();
        return redirect()->route('institutes.operations.users.index')
            ->with('temporary_password_once', $temporaryPassword)
            ->with('temporary_password_user', $user->email)
            ->with('status', 'Password reset. Copy the temporary password now; it will not be shown again.');
    }

    public function editPassword(): View
    {
        return view('institutes.account.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::min(12)->letters()->mixedCase()->numbers()->symbols(), 'confirmed'],
        ], [
            'current_password.current_password' => 'The current password is incorrect.',
            'password.confirmed' => 'The new password confirmation does not match.',
        ]);

        $request->user()->forceFill([
            'password' => $validated['password'],
            // Invalidate remembered-login cookies created before this password change.
            'remember_token' => Str::random(60),
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route('institutes.operations.account.password.edit')
            ->with('status', 'Password changed successfully. Other remembered-login tokens have been invalidated.');
    }
}
