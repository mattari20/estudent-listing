@extends('layouts.app')
@section('title','Institute Operations Login')
@section('content')
<div class="auth-card"><h1>Institute Operations</h1><p>Authorized operators only.</p>
<form method="post" action="{{ route('institutes.login.store') }}">@csrf
<label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
<label>Password<input type="password" name="password" required></label>
<label><input type="checkbox" name="remember" value="1"> Remember me</label>
@if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<button type="submit">Sign in</button></form></div>
@endsection