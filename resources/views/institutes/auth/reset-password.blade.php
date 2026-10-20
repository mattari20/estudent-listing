@extends('layouts.app')
@section('title','Reset Password')
@section('content')
<div class="auth-card">
    <p class="eyebrow">Account recovery</p>
    <h1>Set a new password</h1>
    <p>Choose a new, unique password. This reset link expires and can only be used once.</p>
    @if($errors->any())<div class="info-card" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="post" action="{{ route('institutes.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>Email address
            <input type="email" name="email" value="{{ old('email', $email) }}" required maxlength="255" autocomplete="email">
        </label>
        <label>New password
            <span class="password-input-wrap">
                <input id="reset-password" type="password" name="password" required minlength="12" autocomplete="new-password" data-password-toggle-input aria-describedby="reset-password-policy">
                <button type="button" class="password-toggle" data-password-toggle aria-controls="reset-password" aria-pressed="false">Show password</button>
            </span>
        </label>
        <p id="reset-password-policy">At least 12 characters, including uppercase and lowercase letters, a number, and a symbol. Use a unique password.</p>
        <label>Confirm new password
            <span class="password-input-wrap">
                <input id="reset-password-confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" data-password-toggle-input>
                <button type="button" class="password-toggle" data-password-toggle aria-controls="reset-password-confirmation" aria-pressed="false">Show password</button>
            </span>
        </label>
        <button type="submit">Reset password</button>
    </form>
    <p><a href="{{ route('institutes.login') }}">Back to sign in</a></p>
</div>
@endsection
