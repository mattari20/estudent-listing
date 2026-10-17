<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class InstitutePasswordResetController
{
    public function requestForm(): View
    {
        return view('institutes.auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        // Only operations accounts may use this flow; keep the response generic to avoid account enumeration.
        $user = User::query()->where('email', $request->input('email'))->first();
        if ($user && $user->isOperator()) {
            Password::sendResetLink($request->only('email'));
        }

        return back()->with('status', 'If an authorized account exists for that email address, a password reset link will be sent shortly. Check your inbox and spam folder.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('institutes.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', PasswordRule::min(12)->letters()->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (!$user || !$user->isOperator()) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'This reset link is invalid or expired. Request a new password reset link and try again.']);
        }

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('institutes.login')
                ->with('status', 'Your password has been reset. Please sign in with your new password.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'This reset link is invalid or expired. Request a new password reset link and try again.']);
    }
}
