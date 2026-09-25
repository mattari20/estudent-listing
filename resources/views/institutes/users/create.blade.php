@extends('layouts.app')
@section('title','Create Institute User')
@section('content')
<div class="auth-card">
<p class="eyebrow">Administration</p><h1>Create Institute User</h1>
<p>Use Administrator for full administration or Operator for institute data operations.</p>
<form method="post" action="{{ route('institutes.operations.users.store') }}">
@csrf
<label>Name<input type="text" name="name" value="{{ old('name') }}" required autocomplete="name"></label>
<label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
<label>Role<select name="role" required><option value="operator" @selected(old('role')==='operator')>Operator</option><option value="admin" @selected(old('role')==='admin')>Administrator</option></select></label>
<label>Password<input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
<label>Confirm password<input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></label>
@if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<button type="submit">Create User</button>
</form>
</div>
@endsection
