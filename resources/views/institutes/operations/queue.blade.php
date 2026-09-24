@extends('layouts.app')
@section('title','Institute Operations Queue')
@section('content')
<div class="page-header"><h1>Import Review Queue</h1><form method="post" action="{{ route('institutes.logout') }}">@csrf<button type="submit">Sign out</button></form></div>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@forelse($rows as $row)<article class="institute-card"><h2>Row {{ $row->row_number }}</h2><p>{{ $row->normalized_payload['name'] ?? $row->payload['name'] ?? 'Unnamed' }}</p><p>Status: {{ $row->status }} · Match: {{ $row->match_state }}</p><a href="{{ route('institutes.operations.row',$row) }}">Review row</a></article>@empty<p>No import candidates require review.</p>@endforelse
{{ $rows->links() }}
@endsection