@extends('layouts.app')
@section('title','Review Import Row')
@section('content')
<a href="{{ route('institutes.operations.queue') }}">← Review queue</a>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
<h1>Import Row {{ $importRow->row_number }}</h1>
<p>Status: {{ $importRow->status }} · Match: {{ $importRow->match_state }}</p>
<pre>{{ json_encode($importRow->normalized_payload ?? $importRow->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
<form method="post" action="{{ route('institutes.operations.resolve',$importRow) }}">@csrf<button type="submit">Refresh identity matches</button></form>
<h2>Identity candidates</h2>
@forelse($matches as $match)<div class="institute-card"><strong>{{ $match->institution?->name }}</strong><p>{{ $match->match_type }} · {{ $match->reason }}</p><p>ID: {{ $match->institution_id }}</p></div>@empty<p>No deterministic identity candidate found. This may be a new institution.</p>@endforelse
<h2>Resolution decision</h2>
<form method="post" action="{{ route('institutes.operations.decide',$importRow) }}">@csrf
<label>Decision<select name="decision" required><option value="approved_new">Approve new institution</option><option value="approved_match">Approve existing match</option><option value="rejected">Reject</option><option value="conflict">Mark conflict</option></select></label>
<label>Existing institution ID (required for match)<input type="number" name="institution_id"></label>
<label>Reason<textarea name="reason" required></textarea></label>
<button type="submit">Record decision</button></form>
<form method="post" action="{{ route('institutes.operations.promote',$importRow) }}">@csrf<button type="submit">Promote approved decision</button></form>
@endsection