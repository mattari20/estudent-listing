@extends('layouts.app')
@section('title','Institute Operations Queue')
@section('content')
<div class="page-header"><div><h1>Import Operations</h1><p><a href="{{ route('institutes.operations.enrichment-import.create') }}">Import Campus / Program Enrichment</a></p>@if(auth()->user()?->isAdmin())<a href="{{ route('institutes.operations.users.index') }}">User Management</a>@endif</div><form method="post" action="{{ route('institutes.logout') }}">@csrf<button type="submit">Sign out</button></form></div>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

@include('institutes.operations._admin-navigation')

<h2>Automated Data Discovery</h2>
<div class="institute-card">
    <strong>Run the governed discovery test</strong>
    <p>This retrieves the configured official source, stores the exact source snapshot, captures evidence, extracts programme candidates, checks duplicates, and places the results in the existing review queue. It does not write canonical institute or programme data.</p>
    <form method="post" action="{{ route('institutes.operations.agent.discover') }}" onsubmit="return confirm('Run automated discovery for the CUI Lahore test profile now?');">
        @csrf
        <input type="hidden" name="profile" value="cui-lahore">
        @if(config('institute_ai.enabled'))
            <label style="display:block;margin:10px 0;">
                <input type="checkbox" name="use_ai" value="1">
                Use configured AI extraction adapter
            </label>
        @endif
        <button type="submit" class="button--approve">Run CUI Lahore Discovery Agent</button>
    </form>
</div>

<section class="batch-management" aria-labelledby="batch-management-title">
    <div class="batch-management__heading">
        <div>
            <p class="batch-management__eyebrow">IMPORT CONTROL CENTRE</p>
            <h2 id="batch-management-title">Import batches</h2>
            <p class="batch-management__intro">Search, compare and audit every import batch. Approval and publication are tracked separately.</p>
        </div>
        <div class="batch-management__summary">
            <span>{{ $batches->total() }} batches</span>
            <span>{{ $batches->count() }} on this page</span>
        </div>
    </div>
    <div class="batch-management__filters" role="search" aria-label="Filter import batches">
        <label class="batch-management__search-label" for="batch-search">Search batches</label>
        <input id="batch-search" type="search" placeholder="Search source, parent, batch ID…" autocomplete="off">
        <label for="batch-status-filter">Status</label>
        <select id="batch-status-filter">
            <option value="">All statuses</option>
            <option value="validated">Validated</option>
            <option value="failed">Failed</option>
            <option value="pending">Pending review</option>
            <option value="approved">Approved</option>
            <option value="promoted">Promoted</option>
            <option value="rejected">Rejected</option>
            <option value="conflict">Conflict</option>
        </select>
        <button type="button" id="batch-filter-reset" class="button button--ghost">Clear filters</button>
    </div>
    <div class="batch-table-wrap">
        <table class="batch-table">
            <caption>Import batches, review status, timestamps and row-level outcomes</caption>
            <thead>
                <tr>
                    <th scope="col">Batch / created</th>
                    <th scope="col">Source &amp; entity</th>
                    <th scope="col">Parent institution</th>
                    <th scope="col">Batch status</th>
                    <th scope="col">Review progress</th>
                    <th scope="col">Row outcomes</th>
                    <th scope="col">Last activity</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody id="batch-table-body">
            @forelse($batches as $batch)
                @php
                    $batchStatus = strtolower((string) $batch->status);
                    $pendingCount = max(0, (int) $batch->pending_rows_count - (int) $batch->approved_rows_count);
                    $approvedCount = (int) $batch->approved_rows_count;
                    $promotedCount = (int) $batch->promoted_rows_count;
                    $rejectedCount = (int) $batch->rejected_rows_count;
                    $conflictCount = (int) $batch->conflict_rows_count;
                    $parentInstitution = $batchParentInstitutions[$batch->id] ?? null;
                    $filterText = strtolower(implode(' ', [
                        $batch->batch_key, $batch->id, $batch->source?->name, $batch->source?->source_type,
                        $batch->entity_type, $parentInstitution?->name, $batch->status,
                    ]));
                    $createdAt = $batch->created_at ?? $batch->received_at;
                    $lastActivity = $batch->completed_at ?? $batch->updated_at ?? $batch->received_at ?? $createdAt;
                    $batchSearchStatuses = [$batchStatus];
                    if ($pendingCount > 0) $batchSearchStatuses[] = 'pending';
                    if ($approvedCount > 0) $batchSearchStatuses[] = 'approved';
                    if ($promotedCount > 0) $batchSearchStatuses[] = 'promoted';
                    if ($rejectedCount > 0) $batchSearchStatuses[] = 'rejected';
                    if ($conflictCount > 0) $batchSearchStatuses[] = 'conflict';
                @endphp
                <tr class="batch-table__row" data-search="{{ $filterText }}" data-statuses="{{ implode(' ', $batchSearchStatuses) }}">
                    <td data-label="Batch / created">
                        <strong class="batch-table__batch-key">#{{ $batch->batch_key }}</strong>
                        <span class="batch-table__subtext">{{ $createdAt ? $createdAt->timezone('Asia/Karachi')->format('d M Y, h:i A') . ' PKT' : 'Creation time unavailable' }}</span>
                        <span class="batch-table__subtext">ID: {{ $batch->id }}</span>
                    </td>
                    <td data-label="Source & entity">
                        <strong>{{ $batch->source?->name ?? 'Source not recorded' }}</strong>
                        <span class="batch-table__subtext">{{ ucfirst(str_replace('_', ' ', (string) $batch->entity_type)) }} · {{ $batch->total_rows }} total rows</span>
                        <span class="batch-table__subtext">Source type: {{ $batch->source?->source_type ?? $batch->source_type ?? 'Not recorded' }}</span>
                        @if($batch->source?->url)
                            <a class="batch-table__source-link" href="{{ $batch->source->url }}" target="_blank" rel="noopener noreferrer">Open source ↗</a>
                        @endif
                    </td>
                    <td data-label="Parent institution">
                        @if($batch->entity_type === 'institution')
                            <span class="batch-table__muted">New institution import</span>
                        @elseif($parentInstitution)
                            <a href="{{ route('institutes.show',$parentInstitution) }}">{{ $parentInstitution->name }}</a>
                            <span class="batch-table__subtext">Institution ID: {{ $parentInstitution->id }}</span>
                        @else
                            <span class="batch-table__muted">Parent not resolved</span>
                        @endif
                    </td>
                    <td data-label="Batch status">
                        <span class="batch-status-badge batch-status-badge--{{ preg_replace('/[^a-z0-9_-]/', '', $batchStatus) }}">{{ ucfirst(str_replace('_', ' ', $batchStatus)) }}</span>
                        @if($batchStatus === 'failed')
                            <span class="batch-table__subtext batch-table__warning">Import failed — inspect details</span>
                        @endif
                    </td>
                    <td data-label="Review progress">
                        <div class="batch-progress">
                            <div><span>Pending</span><strong>{{ $pendingCount }}</strong></div>
                            <div><span>Approved</span><strong>{{ $approvedCount }}</strong></div>
                            <div><span>Promoted</span><strong>{{ $promotedCount }}</strong></div>
                            @if($batch->total_rows > 0)
                                <div class="batch-progress__track" role="img" aria-label="{{ $promotedCount }} of {{ $batch->total_rows }} rows promoted">
                                    <span style="width: {{ min(100, round(($promotedCount / max(1, (int) $batch->total_rows)) * 100)) }}%"></span>
                                </div>
                            @endif
                        </div>
                    </td>
                    <td data-label="Row outcomes">
                        <div class="batch-outcomes">
                            <span class="batch-outcome batch-outcome--promoted">Promoted <strong>{{ $promotedCount }}</strong></span>
                            <span class="batch-outcome batch-outcome--rejected">Rejected <strong>{{ $rejectedCount }}</strong></span>
                            <span class="batch-outcome batch-outcome--conflict">Conflicts <strong>{{ $conflictCount }}</strong></span>
                            <span class="batch-outcome batch-outcome--invalid">Invalid <strong>{{ $batch->invalid_rows }}</strong></span>
                        </div>
                    </td>
                    <td data-label="Last activity">
                        <strong>{{ $lastActivity ? $lastActivity->timezone('Asia/Karachi')->format('d M Y') : 'Not recorded' }}</strong>
                        <span class="batch-table__subtext">{{ $lastActivity ? $lastActivity->timezone('Asia/Karachi')->format('h:i A') . ' PKT' : 'Timestamp unavailable' }}</span>
                        @if($batch->completed_at)
                            <span class="batch-table__subtext">Completed {{ $batch->completed_at->timezone('Asia/Karachi')->format('d M Y, h:i A') }} PKT</span>
                        @endif
                    </td>
                    <td data-label="Actions">
                        <div class="batch-table__actions">
                            <a class="button button--ghost" href="{{ route('institutes.operations.queue',['batch_id'=>$batch->id]) }}">Review batch</a>
                            @if($batch->entity_type === 'institution' && $pendingCount > 0)
                                <form method="post" action="{{ route('institutes.operations.imports.approve-and-promote-new',$batch) }}" onsubmit="return confirm('Approve and promote all unmatched new institutions in this batch? Rows with identity matches or conflicts will remain for individual review.');">
                                    @csrf
                                    <input type="hidden" name="reason" value="Bulk operator approval of unmatched new institutions from this import batch.">
                                    <button type="submit" class="button--approve">Approve &amp; promote eligible</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="batch-table__empty">No import batches have been recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
        <p id="batch-no-results" class="batch-table__empty" hidden>No batches match these filters. Clear filters to see all batches.</p>
    </div>
    <div class="batch-management__footer">
        <span>All timestamps shown in Pakistan Standard Time (PKT).</span>
        <span>Approval does not publish data; promotion is tracked separately.</span>
    </div>
    {{ $batches->links() }}
</section>

<style>
.batch-management{margin:24px 0 32px;color:var(--text-color,#172033)}
.batch-management__heading{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:18px}
.batch-management__eyebrow{font-size:.72rem;font-weight:800;letter-spacing:.12em;color:#2563eb;margin:0 0 6px}
.batch-management__heading h2{margin:0 0 6px}
.batch-management__intro{margin:0;color:#64748b;max-width:720px}
.batch-management__summary{display:flex;gap:8px;flex-wrap:wrap}
.batch-management__summary span{background:#eff6ff;border:1px solid #dbeafe;border-radius:999px;padding:7px 11px;font-size:.8rem;font-weight:700;color:#1d4ed8;white-space:nowrap}
.batch-management__filters{display:grid;grid-template-columns:minmax(220px,1fr) minmax(160px,220px) auto;gap:10px;align-items:end;background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:14px;margin-bottom:14px}
.batch-management__filters label{font-size:.8rem;font-weight:700;color:#334155}
.batch-management__search-label{grid-column:1}
.batch-management__filters input,.batch-management__filters select{width:100%;min-width:0;min-height:42px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;padding:8px 10px;color:#0f172a}
.batch-management__filters input{grid-column:1;grid-row:2}
.batch-management__filters label[for="batch-status-filter"]{grid-column:2;grid-row:1}
.batch-management__filters select{grid-column:2;grid-row:2}
.batch-management__filters button{grid-column:3;grid-row:2;min-height:42px}
.batch-table-wrap{overflow-x:auto;border:1px solid #dbe2ea;border-radius:14px;background:#fff}
.batch-table{border-collapse:separate;border-spacing:0;width:100%;min-width:1180px;font-size:.86rem}
.batch-table caption{padding:12px 16px;text-align:left;color:#64748b;background:#f8fafc;font-size:.78rem}
.batch-table th{position:sticky;top:0;background:#eef3f9;color:#334155;text-align:left;font-size:.72rem;letter-spacing:.035em;text-transform:uppercase;padding:13px 12px;border-top:1px solid #dbe2ea;border-bottom:1px solid #dbe2ea;white-space:nowrap}
.batch-table td{vertical-align:top;padding:14px 12px;border-bottom:1px solid #edf0f4;max-width:260px}
.batch-table tbody tr:last-child td{border-bottom:0}
.batch-table tbody tr:hover{background:#f8fbff}
.batch-table__batch-key{display:block;overflow-wrap:anywhere;font-size:.8rem}
.batch-table__subtext{display:block;margin-top:5px;color:#64748b;font-size:.75rem;line-height:1.45}
.batch-table__source-link{display:inline-block;margin-top:6px;font-size:.76rem}
.batch-table__muted{color:#64748b;font-size:.8rem}
.batch-table__warning{color:#b45309;font-weight:700}
.batch-status-badge{display:inline-flex;align-items:center;border-radius:999px;padding:5px 9px;font-size:.74rem;font-weight:800;white-space:nowrap;background:#e2e8f0;color:#334155}
.batch-status-badge--validated,.batch-status-badge--completed{background:#dcfce7;color:#166534}
.batch-status-badge--failed,.batch-status-badge--error{background:#fee2e2;color:#991b1b}
.batch-status-badge--pending,.batch-status-badge--waiting,.batch-status-badge--processing{background:#fef3c7;color:#92400e}
.batch-status-badge--approved{background:#dbeafe;color:#1d4ed8}
.batch-status-badge--promoted{background:#d1fae5;color:#065f46}
.batch-status-badge--rejected{background:#fee2e2;color:#991b1b}
.batch-progress{min-width:110px}
.batch-progress>div:not(.batch-progress__track){display:flex;justify-content:space-between;gap:12px;margin:0 0 5px;font-size:.76rem}
.batch-progress>div span{color:#64748b}
.batch-progress__track{height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-top:9px}
.batch-progress__track span{display:block;height:100%;background:#059669;border-radius:99px}
.batch-outcomes{display:flex;flex-wrap:wrap;gap:5px;min-width:130px}
.batch-outcome{display:inline-flex;gap:5px;align-items:center;border-radius:6px;padding:4px 6px;font-size:.7rem;white-space:nowrap;background:#f1f5f9;color:#475569}
.batch-outcome--promoted{background:#dcfce7;color:#166534}
.batch-outcome--rejected{background:#fee2e2;color:#991b1b}
.batch-outcome--conflict{background:#ffedd5;color:#9a3412}
.batch-outcome--invalid{background:#fef3c7;color:#92400e}
.batch-table__actions{display:flex;flex-direction:column;align-items:stretch;gap:7px;min-width:125px}
.batch-table__actions .button,.batch-table__actions button{font-size:.76rem;white-space:normal;line-height:1.35}
.batch-table__actions form{margin:0}
.batch-table__empty{padding:22px!important;text-align:center;color:#64748b}
.batch-management__footer{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px 3px;color:#64748b;font-size:.76rem}
@media(max-width:760px){.batch-management__heading{align-items:flex-start;flex-direction:column}.batch-management__filters{grid-template-columns:1fr}.batch-management__filters label,.batch-management__filters input,.batch-management__filters select,.batch-management__filters button{grid-column:1;grid-row:auto}.batch-table{min-width:1050px}}
</style>

<script>
(function () {
    const search = document.getElementById('batch-search');
    const status = document.getElementById('batch-status-filter');
    const reset = document.getElementById('batch-filter-reset');
    const rows = Array.from(document.querySelectorAll('.batch-table__row'));
    const empty = document.getElementById('batch-no-results');
    function filterBatches() {
        const query = (search?.value || '').trim().toLowerCase();
        const selectedStatus = status?.value || '';
        let visible = 0;
        rows.forEach(function (row) {
            const textMatches = !query || (row.dataset.search || '').includes(query);
            const statuses = (row.dataset.statuses || '').split(' ');
            const statusMatches = !selectedStatus || statuses.includes(selectedStatus);
            row.hidden = !(textMatches && statusMatches);
            if (!row.hidden) visible++;
        });
        if (empty) empty.hidden = visible > 0 || rows.length === 0;
    }
    search?.addEventListener('input', filterBatches);
    status?.addEventListener('change', filterBatches);
    reset?.addEventListener('click', function () {
        if (search) search.value = '';
        if (status) status.value = '';
        filterBatches();
    });
})();
</script>
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
        <div class="import-row-card__top-actions">
            @if($state === 'waiting' && $row->status === 'candidate')
            <form method="post" action="{{ route('institutes.operations.quick-approve',$row) }}" onsubmit="return confirm('Approve this row without opening the detailed review? The row will be approved but will not be promoted automatically.');">
                @csrf
                <button type="submit" class="button--approve">Approve</button>
            </form>
            @endif
            <a class="button button--ghost" href="{{ route('institutes.operations.row',$row) }}">Review details →</a>
            @if($state !== 'promoted')
            <form method="post" action="{{ route('institutes.operations.reject',$row) }}" onsubmit="return confirm('Reject this import row and remove it from the active review queue? The import history will be preserved.');">
                @csrf
                <input type="hidden" name="reason" value="{{ $state === 'duplicate' ? 'Duplicate import identified during review.' : 'Operator rejected this import row from the review queue.' }}">
                <button type="submit" class="button--danger">{{ $state === 'duplicate' ? 'Reject duplicate' : 'Reject row' }}</button>
            </form>
            @endif
        </div>
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