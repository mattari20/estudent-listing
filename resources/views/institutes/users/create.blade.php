@extends('layouts.app')
@section('title','Create Institute User')
@section('content')
<div class="auth-card">
<p class="eyebrow">Administration</p>
<h1>Create Institute User</h1>
<p>Create an account for a trusted team member. Administrators can manage accounts; Operators can manage institute data.</p>
<p class="info-card">A strong temporary password will be generated automatically and displayed once after account creation. The new user must choose a private password at first login. Temporary passwords are random and are never based on usernames or email addresses.</p>
@if($errors->any())<div class="info-card" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form method="post" action="{{ route('institutes.operations.users.store') }}">
@csrf
<label>Name<input type="text" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name"></label>
<label>Email<input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"></label>
<label>Role<select name="role" required><option value="operator" @selected(old('role', 'operator')==='operator')>Operator — institute data operations</option><option value="admin" @selected(old('role')==='admin')>Administrator — full administration</option></select></label>
<button type="submit">Create User & Generate Temporary Password</button>
</form>
</div>
@endsection
