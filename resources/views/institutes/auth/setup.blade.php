@extends('layouts.app')
@section('title','Create Institute Administrator')
@section('content')
<div class="auth-card">
<h1>Create Institute Administrator</h1>
<p>This one-time setup creates the first administrator account for the Institutes Directory.</p>
<form method="post" action="{{ route('institutes.setup.store') }}">
@csrf
<label>Name<input type="text" name="name" value="{{ old('name') }}" required autocomplete="name"></label>
<label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
<label>Password
<span class="password-input-wrap">
<input id="setup-password" type="password" name="password" required minlength="12" autocomplete="new-password" data-password-toggle-input>
<button type="button" class="password-toggle" data-password-toggle aria-controls="setup-password" aria-pressed="false">Show password</button>
</span>
</label>
<label>Confirm password
<span class="password-input-wrap">
<input id="setup-password-confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" data-password-toggle-input>
<button type="button" class="password-toggle" data-password-toggle aria-controls="setup-password-confirmation" aria-pressed="false">Show password</button>
</span>
</label>
@if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<button type="submit">Create Administrator</button>
</form>
</div>
@endsection
