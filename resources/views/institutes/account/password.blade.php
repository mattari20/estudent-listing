@extends('layouts.app')
@section('title','Change Password')
@section('content')
<div class="auth-card">
    <p class="eyebrow">Account security</p>
    <h1>Change Password</h1>
    <p>Confirm your current password before setting a new one. Use a unique password that you do not use on another website.</p>
    @if(session('status'))<div class="info-card" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="info-card" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="post" action="{{ route('institutes.operations.account.password.update') }}">
        @csrf
        @method('PUT')
        <label>Current password
            <span class="password-input-wrap">
                <input id="current-password" type="password" name="current_password" required autocomplete="current-password" data-password-toggle-input>
                <button type="button" class="password-toggle" data-password-toggle aria-controls="current-password" aria-pressed="false">Show password</button>
            </span>
        </label>
        <label>New password
            <span class="password-input-wrap">
                <input id="change-password" type="password" name="password" required minlength="12" autocomplete="new-password" data-password-toggle-input aria-describedby="password-policy">
                <button type="button" class="password-toggle" data-password-toggle aria-controls="change-password" aria-pressed="false">Show password</button>
            </span>
        </label>
        <p id="password-policy">At least 12 characters, including uppercase and lowercase letters, a number, and a symbol. A longer unique passphrase is recommended.</p>
        <label>Confirm new password
            <span class="password-input-wrap">
                <input id="change-password-confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" data-password-toggle-input>
                <button type="button" class="password-toggle" data-password-toggle aria-controls="change-password-confirmation" aria-pressed="false">Show password</button>
            </span>
        </label>
        <button type="submit">Update Password</button>
    </form>
</div>
@endsection
