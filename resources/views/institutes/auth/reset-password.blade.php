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
            <input type="password" name="password" required minlength="12" autocomplete="new-password" aria-describedby="reset-password-policy">
        </label>
        <p id="reset-password-policy">At least 12 characters, including uppercase and lowercase letters, a number, and a symbol. Use a unique password.</p>
        <label>Confirm new password
            <input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password">
        </label>
        <button type="submit">Reset password</button>
    </form>
    <p><a href="{{ route('institutes.login') }}">Back to sign in</a></p>
</div>
@endsection
