@extends('layouts.app')
@section('title','Institute Operations Queue')
@section('content')
<div class="page-header"><div><h1>Import Operations</h1><p><a href="{{ route('institutes.operations.enrichment-import.create') }}">Import Campus / Program Enrichment</a></p>@if(auth()->user()?->isAdmin())<a href="{{ route('institutes.operations.users.index') }}">User Management</a>@endif</div><form method="post" action="{{ route('institutes.logout') }}">@csrf<button type="submit">Sign out</button></form></div>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

@include('institutes.operations._admin-navigation')


<h2>Import batches</h2>
@forelse($batches as $batch)
<article class="institute-card">
    <h3>Batch {{ $batch->batch_key }}</h3>
    <p>{{ $batch->source?->name }} · {{ $batch->status }} · {{ $batch->total_rows }} total rows</p>
    <p><strong>Waiting:</strong> {{ $batch->pending_rows_count }} · <strong>Approved:</strong> {{ $batch->approved_rows_count }} · <strong>Promoted:</strong> {{ $batch->promoted_rows_count }} · <strong>Rejected:</strong> {{ $batch->rejected_rows_count }} · <strong>Conflict:</strong> {{ $batch->conflict_rows_count }}</p>
    <p><a href="{{ route('institutes.operations.queue',['batch_id'=>$batch->id]) }}">Review / Approve batch →</a></p>
    @if($batch->entity_type !== 'institution' && isset($batchParentInstitutions[$batch->id]))
        <p><a href="{{ route('institutes.show',$batchParentInstitutions[$batch->id]) }}">View parent institute →</a></p>
    @endif
    @if($batch->entity_type === 'institution' && $batch->pending_rows_count > 0)
    <form method="post" action="{{ route('institutes.operations.imports.approve-and-promote-new',$batch) }}" onsubmit="return confirm('Approve and promote all unmatched new institutions in this batch? Rows with identity matches or conflicts will remain for individual review.');">
        @csrf
        <input type="hidden" name="reason" value="Bulk operator approval of unmatched new institutions from this import batch.">
        <button type="submit">Approve &amp; Promote All Unmatched New Institutions</button>
    </form>
    @endif
</article>
@empty<p>No import batches.</p>@endforelse
{{ $batches->links() }}

@if($qualityReport)<h2>Import quality report</h2><div class="filter-grid">@foreach($qualityReport as $key=>$value)<div class="institute-card"><strong>{{ ucfirst(str_replace('_',' ',$key)) }}</strong><div>{{ is_float($value) ? number_format($value*100,2).'%' : $value }}</div></div>@endforeach</div>@endif
@if($summary)<h2>Batch reconciliation</h2><div class="filter-grid">@foreach($summary as $key=>$value)<div class="institute-card"><strong>{{ ucfirst(str_replace('_',' ',$key)) }}</strong><div>{{ $value }}</div></div>@endforeach</div>@endif

<h2>Reverification queue</h2>
@forelse($reverification as $item)<article class="institute-card"><strong>{{ $item->reason }}</strong><p>{{ $item->priority }} · {{ $item->status }} · Subject {{ class_basename($item->subject_type) }} #{{ $item->subject_id }}</p><p>Observed {{ $item->observed_at }}</p><form method="post" action="{{ route('institutes.operations.reverification.start',$item) }}">@csrf<input name="reason" required placeholder="Review reason"><button type="submit">Start review</button></form></article>@empty<p>No open reverification items.</p>@endforelse

<h2>Review rows</h2>
@if($batchId)
<div class="institute-card">
    <strong>Pending review: {{ $rows->total() }}</strong>
    <p>Individual review records a decision, but the row leaves this queue only after promotion. You can now approve and promote all unmatched new institutions in this batch in one action.</p>
    <form method="post" action="{{ route('institutes.operations.imports.approve-and-promote-new',$batchId) }}" onsubmit="return confirm('Approve and publish all candidate institutions in this batch that have no identity match candidates or open conflicts? Rows with matches or conflicts will remain for individual review.');">
        @csrf
        <label for="bulk-new-reason"><strong>Bulk approval reason</strong></label>
        <textarea id="bulk-new-reason" name="reason" required rows="3">Reviewed test/import batch; approve unmatched institutions as new canonical records.</textarea>
        <button type="submit">Approve &amp; Promote All Unmatched New Institutions</button>
    </form>
</div>
@endif

<form method="post" action="{{ route('institutes.operations.bulk-promote') }}">@csrf
<div class="institute-card">
    <label><input type="checkbox" id="select-all-rows"> <strong>Select all rows on this page</strong></label>
    <p>{{ $rows->total() }} import row(s) in this batch/query.</p>
<div class="import-status-legend">
<span class="status-badge status-badge--waiting">Waiting</span>
<span class="status-badge status-badge--approved">Approved</span>
<span class="status-badge status-badge--promoted">Promoted</span>
<span class="status-badge status-badge--duplicate">Duplicate</span>
<span class="status-badge status-badge--invalid">Needs correction</span>
</div>
</div>
@forelse($rows as $row)
@php
    $decision = $rowDecisionMap[$row->id] ?? null;
    $state = $row->status === 'promoted' ? 'promoted' : ($row->status === 'duplicate' ? 'duplicate' : ($row->status === 'invalid' ? 'invalid' : ($decision === 'approved' || $decision === 'approved_new' || $decision === 'approved_match' ? 'approved' : ($decision === 'rejected' ? 'rejected' : ($decision === 'conflict' ? 'conflict' : 'waiting')))));
    $stateLabel = match($state) { 'approved' => 'Approved — ready for promotion', 'promoted' => 'Promoted', 'duplicate' => 'Duplicate — review existing record', 'invalid' => 'Needs correction', 'rejected' => 'Rejected', 'conflict' => 'Conflict', default => 'Waiting for approval' };
@endphp
<article class="import-row-card import-row-card--{{ $state }}">
    <div class="import-row-card__top">
        <div>
            <span class="status-badge status-badge--{{ $state }}">{{ $stateLabel }}</span>
            <h3>{{ $row->normalized_payload['name'] ?? $row->payload['name'] ?? 'Unnamed' }}</h3>
            <p class="import-row-card__meta">Row {{ $row->row_number }} · {{ ucfirst($row->batch->entity_type) }} · {{ $row->match_state }}</p>
        </div>
        <a class="button button--ghost" href="{{ route('institutes.operations.row',$row) }}">Review details →</a>
    </div>
    @if($row->notes || $row->validation_errors)
        <div class="import-row-card__notice">{{ $row->notes ?? implode(' ', $row->validation_errors ?? []) }}</div>
    @endif
    <div class="import-row-card__actions">
        @if($state === 'waiting')<strong>Action required: review and approve this row.</strong>
        @elseif($state === 'approved')<strong>Approved. Next action: promote to canonical data.</strong>
        @elseif($state === 'duplicate')<strong>Not publishable until the existing record/import is reviewed.</strong>
        @endif
    </div>
    @if($state === 'waiting' || $state === 'approved')
        <label class="import-row-card__select"><input class="row-checkbox" type="checkbox" name="row_ids[]" value="{{ $row->id }}"> Select for bulk promotion</label>
    @endif
</article>
@empty<p>No import rows match the current queue.</p>@endforelse
<button type="submit">Promote selected approved rows</button></form>
{{ $rows->links() }}

<script>
document.getElementById('select-all-rows')?.addEventListener('change', function () {
    document.querySelectorAll('.row-checkbox').forEach(function (checkbox) {
        checkbox.checked = this.checked;
    }, this);
});
</script>
@endsection