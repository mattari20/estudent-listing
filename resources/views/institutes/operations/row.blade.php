@extends('layouts.app')
@section('title','Review Import Row')
@section('content')
@include('institutes.operations._admin-navigation')
<p><a href="{{ route('institutes.operations.queue',['batch_id'=>$importRow->import_batch_id]) }}">← Review this batch</a> · <a href="{{ route('institutes.operations.queue') }}">Operations Hub</a></p>
@if($parentInstitution)
<p><a href="{{ route('institutes.show',$parentInstitution) }}">View parent institute: {{ $parentInstitution->name }} →</a></p>
@endif
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<h1>Import Row {{ $importRow->row_number }}</h1>
@if(in_array($importRow->status, ['candidate','validated','duplicate','invalid'], true))
<div class="import-row-action import-row-action--danger">
<h2>Row action</h2>
<p>If this row is a duplicate, accidental double import, or otherwise should not be processed, reject it here. <strong>Rejecting removes it from the active review queue but keeps the import record and audit history.</strong> It does not delete canonical institute data.</p>
<form method="post" action="{{ route('institutes.operations.reject',$importRow) }}" onsubmit="return confirm('Reject this import row and remove it from the active review queue? The import history will be preserved.');">
@csrf
<label>Rejection reason<textarea name="reason" required placeholder="Example: Duplicate test import — the same institute was already imported."></textarea></label>
<button type="submit" class="button--danger">Reject / Remove from Review</button>
</form>
</div>
@endif
<p>Status: <span class="status-badge status-badge--{{ $importRow->status }}">{{ ucfirst($importRow->status) }}</span> · Match: {{ $importRow->match_state }} · Entity: {{ $importRow->batch->entity_type }}</p>
<h2>Import conflicts</h2>
@if($conflicts->isNotEmpty())<ul>@foreach($conflicts as $conflict)<li><strong>{{ $conflict->conflict_type }}</strong> · {{ $conflict->severity }} · {{ $conflict->status }} — {{ $conflict->description }} @if($conflict->status==='open')<form method="post" action="{{ route('institutes.operations.conflict-resolve',$importRow) }}">@csrf<input type="hidden" name="conflict_id" value="{{ $conflict->id }}"><label>Resolution reason<textarea name="reason" required></textarea></label><button type="submit">Resolve conflict</button></form>@else<p>Resolved: {{ $conflict->resolution_reason }}</p>@endif</li>@endforeach</ul>@else<p>No conflicts recorded.</p>@endif
<h2>Promotion history</h2>
@if($history->isNotEmpty())<ol>@foreach($history as $event)<li><strong>{{ $event->event_type }}</strong> — {{ $event->occurred_at }} — {{ $event->reason }} @if($event->canonical_subject_id)(Canonical ID {{ $event->canonical_subject_id }})@endif</li>@endforeach</ol>@else<p>No promotion events recorded yet.</p>@endif
<h2>Dependency chain</h2>
@if($dependency['chain'])<ol>@foreach($dependency['chain'] as $entity)<li><strong>{{ class_basename($entity) }}</strong>: {{ $entity->name ?? $entity->label ?? ('#'.$entity->id) }} (ID {{ $entity->id }})</li>@endforeach</ol>@else<p>No canonical parent chain is currently available.</p>@endif
<h2>Imported data</h2><pre>{{ json_encode($importRow->normalized_payload ?? $importRow->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
@if($importRow->status==='duplicate')
<div class="import-row-card import-row-card--duplicate"><strong>Duplicate import</strong><p>{{ $importRow->notes ?? 'This row matches data that is already present or was imported previously.' }}</p><p>This system does not overwrite canonical records automatically.</p></div>
@endif
@if($importRow->batch->entity_type==='institution')
<form method="post" action="{{ route('institutes.operations.resolve',$importRow) }}">@csrf<button type="submit">Refresh identity matches</button></form>
<h2>Identity candidates</h2>
@forelse($matches as $match)<div class="institute-card"><strong>{{ $match->institution?->name }}</strong><p>{{ $match->match_type }} · {{ $match->reason }}</p><p>ID: {{ $match->institution_id }}</p></div>@empty<p>No deterministic identity candidate found. This may be a new institution.</p>@endforelse
<h2>Official source evidence</h2>
<p>Promotion requires evidence tied to this exact import row. Open the institution's official website, verify the institution name, then enter the official page URL and a short excerpt copied from that page. Do not enter invented or AI-generated text as source evidence.</p>
<form method="post" action="{{ route('institutes.operations.row.evidence.store',$importRow) }}">
@csrf
<label>Official page URL<input type="url" name="reference" required maxlength="2048" placeholder="https://official-institution-domain/..."></label>
<label>Verified excerpt<textarea name="excerpt" required minlength="20" maxlength="10000" placeholder="Paste the relevant wording from the official page showing the institution's name."></textarea></label>
<button type="submit">Record official source evidence</button>
</form>
<h2>Identity decision</h2>
@if($identityDecision)<p>Latest: <strong>{{ $identityDecision->decision }}</strong> — {{ $identityDecision->reason }}</p>@endif
@if($importRow->status==='candidate')
<form method="post" action="{{ route('institutes.operations.decide',$importRow) }}">@csrf
<label>Decision<select name="decision" required><option value="approved_new">Approve new institution</option><option value="approved_match">Approve existing match</option><option value="rejected">Reject</option><option value="conflict">Mark conflict</option></select></label>
<label>Existing institution ID<input type="number" name="institution_id"></label>
<label>Reason<textarea name="reason" required></textarea></label><button type="submit">Record decision</button></form>
@if($identityDecision && in_array($identityDecision->decision, ['approved_new', 'approved_match'], true))
<form method="post" action="{{ route('institutes.operations.promote',$importRow) }}">@csrf<button type="submit">Promote approved institution</button></form>
@else
<p><strong>Next step:</strong> Record an approved identity decision above before promotion.</p>
@endif
@endif
@else
<h2>Enrichment promotion decision</h2>
@if($enrichmentDecision)<p>Latest: <strong>{{ $enrichmentDecision->decision }}</strong> — {{ $enrichmentDecision->reason }}</p>@endif
@if($importRow->status==='candidate')
<form method="post" action="{{ route('institutes.operations.enrichment-decision',$importRow) }}">@csrf
<label>Decision<select name="decision" required><option value="approved">Approve promotion</option><option value="rejected">Reject</option><option value="conflict">Mark conflict</option></select></label>
<label>Reason<textarea name="reason" required></textarea></label><button type="submit">Record enrichment decision</button></form>
@if($enrichmentDecision?->decision==='approved')<form method="post" action="{{ route('institutes.operations.enrichment-promote',$importRow) }}">@csrf<button type="submit">Promote approved enrichment</button></form>@endif
<p>Bulk promotion is allowed only after an explicit approved enrichment decision.</p>
@endif
@endif
@if($importRow->status==='promoted')
@if($parentInstitution)<p><strong>Published canonical record:</strong> <a href="{{ route('institutes.show',$parentInstitution) }}">View public institute page →</a></p>@endif
<h2>Safe reversal</h2>
<p>Reversal never hard-deletes provenance. Existing canonical matches are preserved.</p>
<form method="post" action="{{ route('institutes.operations.reverse',$importRow) }}">@csrf
<label>Reason<textarea name="reason" required></textarea></label><button type="submit">Reverse promotion safely</button></form>
@endif
@endsection