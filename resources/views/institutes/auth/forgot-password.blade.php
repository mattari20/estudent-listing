@extends('layouts.app')
@section('title','Forgot Password')
@section('content')
<div class="auth-card">
    <p class="eyebrow">Account recovery</p>
    <h1>Forgot your password?</h1>
    <p>Enter the email address associated with your institute operations account. If an authorized account exists, we will send a time-limited reset link.</p>
    @if(session('status'))<div class="info-card" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="info-card" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="post" action="{{ route('institutes.password.email') }}">
        @csrf
        <label>Email address
            <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
        </label>
        <button type="submit">Email reset link</button>
    </form>
    <p><a href="{{ route('institutes.login') }}">Back to sign in</a></p>
</div>
@endsection
