<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class InstituteSetupController
{
    public function create(): View|RedirectResponse
    {
        if (User::whereIn('role', ['operator', 'admin'])->exists()) {
            return redirect()->route('institutes.login');
        }

        return view('institutes.auth.setup');
    }

    public function store(Request $request): RedirectResponse
    {
        if (User::whereIn('role', ['operator', 'admin'])->exists()) {
            return redirect()->route('institutes.login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
        ]);

        return redirect()
            ->route('institutes.login')
            ->with('status', 'Administrator account created. You can now sign in.');
    }
}
