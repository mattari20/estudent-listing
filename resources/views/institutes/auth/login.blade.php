@extends('layouts.app')
@section('title','Institute Operations Login')
@section('content')
<div class="auth-card"><h1>Institute Operations</h1><p>Authorized operators only.</p>
<form method="post" action="{{ route('institutes.login.store') }}">@csrf
<label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"></label>
<label>Password
<span class="password-input-wrap">
<input id="login-password" type="password" name="password" required autocomplete="current-password" data-password-toggle-input>
<button type="button" class="password-toggle" data-password-toggle aria-controls="login-password" aria-pressed="false">Show password</button>
</span>
</label>
<p><a href="{{ route('institutes.password.request') }}">Forgot your password?</a></p>
<label><input type="checkbox" name="remember" value="1"> Remember me</label>
@if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<button type="submit">Sign in</button></form></div>
@endsection