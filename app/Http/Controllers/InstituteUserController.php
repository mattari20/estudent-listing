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
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route('institutes.operations.account.password.edit')
            ->with('status', 'Password changed successfully. Other remembered-login tokens have been invalidated.');
    }
}
