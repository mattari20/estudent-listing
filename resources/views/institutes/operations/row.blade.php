@extends('layouts.app')
@section('title','Review Import Row')
@section('content')
<a href="{{ route('institutes.operations.queue') }}">← Review queue</a>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
<h1>Import Row {{ $importRow->row_number }}</h1>
<p>Status: {{ $importRow->status }} · Match: {{ $importRow->match_state }} · Entity: {{ $importRow->batch->entity_type }}</p>
<h2>Dependency chain</h2>
@if($dependency['chain'])<ol>@foreach($dependency['chain'] as $entity)<li><strong>{{ class_basename($entity) }}</strong>: {{ $entity->name ?? $entity->label ?? ('#'.$entity->id) }} (ID {{ $entity->id }})</li>@endforeach</ol>@else<p>No canonical parent chain is currently available.</p>@endif
<h2>Imported data</h2><pre>{{ json_encode($importRow->normalized_payload ?? $importRow->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
@if($importRow->batch->entity_type==='institution')
<form method="post" action="{{ route('institutes.operations.resolve',$importRow) }}">@csrf<button type="submit">Refresh identity matches</button></form>
<h2>Identity candidates</h2>
@forelse($matches as $match)<div class="institute-card"><strong>{{ $match->institution?->name }}</strong><p>{{ $match->match_type }} · {{ $match->reason }}</p><p>ID: {{ $match->institution_id }}</p></div>@empty<p>No deterministic identity candidate found. This may be a new institution.</p>@endforelse
<h2>Identity decision</h2>
@if($identityDecision)<p>Latest: <strong>{{ $identityDecision->decision }}</strong> — {{ $identityDecision->reason }}</p>@endif
<form method="post" action="{{ route('institutes.operations.decide',$importRow) }}">@csrf
<label>Decision<select name="decision" required><option value="approved_new">Approve new institution</option><option value="approved_match">Approve existing match</option><option value="rejected">Reject</option><option value="conflict">Mark conflict</option></select></label>
<label>Existing institution ID<input type="number" name="institution_id"></label>
<label>Reason<textarea name="reason" required></textarea></label><button type="submit">Record decision</button></form>
<form method="post" action="{{ route('institutes.operations.promote',$importRow) }}">@csrf<button type="submit">Promote approved institution</button></form>
@else
<h2>Enrichment promotion decision</h2>
@if($enrichmentDecision)<p>Latest: <strong>{{ $enrichmentDecision->decision }}</strong> — {{ $enrichmentDecision->reason }}</p>@endif
<form method="post" action="{{ route('institutes.operations.enrichment-decision',$importRow) }}">@csrf
<label>Decision<select name="decision" required><option value="approved">Approve promotion</option><option value="rejected">Reject</option><option value="conflict">Mark conflict</option></select></label>
<label>Reason<textarea name="reason" required></textarea></label><button type="submit">Record enrichment decision</button></form>
<p>Bulk promotion is allowed only after an explicit approved enrichment decision.</p>
@endif
@endsection