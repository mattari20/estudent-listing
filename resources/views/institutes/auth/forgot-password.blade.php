@extends('layouts.app')
@section('title','Forgot Password')
@section('content')
<div class="auth-card">
    <p class="eyebrow">Account recovery</p>
    <h1>Forgot your password?</h1>
    <p>Email-based recovery is not enabled for this installation. Contact an authorized administrator to reset your account. If both administrators are locked out, use the documented hosting-assisted emergency recovery procedure.</p>
    @if(session('status'))<div class="info-card" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="info-card" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="post" action="{{ route('institutes.password.email') }}">
        @csrf
        <label>Email address
            <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
        </label>
        <button type="submit" disabled aria-disabled="true">Email recovery unavailable</button>
    </form>
    <p><a href="{{ route('institutes.login') }}">Back to sign in</a></p>
</div>
@endsection
