@extends('layouts.app')
@section('title','Institute Users')
@section('content')
<div class="section-heading">
<div><p class="eyebrow">Administration</p><h1>Institute Users</h1></div>
<a class="button" href="{{ route('institutes.operations.users.create') }}">Create User</a>
</div>
@if(session('status'))<div class="info-card">{{ session('status') }}</div>@endif
<div class="data-block">
<table style="width:100%;border-collapse:collapse">
<thead><tr><th style="text-align:left;padding:10px">Name</th><th style="text-align:left;padding:10px">Email</th><th style="text-align:left;padding:10px">Role</th></tr></thead>
<tbody>
@foreach($users as $user)
<tr><td style="padding:10px;border-top:1px solid var(--line)">{{ $user->name }}</td><td style="padding:10px;border-top:1px solid var(--line)">{{ $user->email }}</td><td style="padding:10px;border-top:1px solid var(--line)">{{ ucfirst($user->role) }}</td></tr>
@endforeach
</tbody>
</table>
</div>
<div class="pagination-wrap">{{ $users->links() }}</div>
@endsection
