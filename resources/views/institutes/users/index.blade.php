@extends('layouts.app')
@section('title','Institute Users')
@section('content')
<div class="section-heading">
<div><p class="eyebrow">Administration</p><h1>Institute Users</h1></div>
<a class="button" href="{{ route('institutes.operations.users.create') }}">Create User</a>
</div>
@if(session('status'))<div class="info-card" role="status">{{ session('status') }}</div>@endif
@if(session('temporary_password_once'))<div class="info-card" role="alert"><h2>Temporary password — shown once</h2><p>Account: {{ session('temporary_password_user') }}</p><p><code>{{ session('temporary_password_once') }}</code></p><p>Copy this now and share it privately. The account must change it at next login.</p></div>@endif
<div class="data-block">
<table style="width:100%;border-collapse:collapse">
<thead><tr><th style="text-align:left;padding:10px">Name</th><th style="text-align:left;padding:10px">Email</th><th style="text-align:left;padding:10px">Role</th><th style="text-align:left;padding:10px">Recovery</th></tr></thead>
<tbody>
@foreach($users as $user)
<tr><td style="padding:10px;border-top:1px solid var(--line)">{{ $user->name }}</td><td style="padding:10px;border-top:1px solid var(--line)">{{ $user->email }}</td><td style="padding:10px;border-top:1px solid var(--line)">{{ ucfirst($user->role) }}</td><td style="padding:10px;border-top:1px solid var(--line)">@if((int) auth()->id() !== (int) $user->id)<form method="post" action="{{ route('institutes.operations.users.reset-password', $user) }}" onsubmit="return confirm('Reset this account password? A temporary password will be shown once.');">@csrf<button type="submit">Reset password</button></form>@else<small>Use Change Password</small>@endif</td></tr>
@endforeach
</tbody>
</table>
</div>
<div class="pagination-wrap">{{ $users->links() }}</div>
@endsection
