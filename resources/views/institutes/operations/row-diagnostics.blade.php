@extends('layouts.app')

@section('title', 'Institution Import Diagnostics')

@section('content')
<div class="page-header">
    <div>
        <h1>Institution Import Diagnostics</h1>
        <p>Read-only report. No import rows or institutions are changed by viewing this page.</p>
        <p><a href="{{ route('institutes.operations.queue', ['batch_id' => $report['batch']['id']]) }}">← Return to this batch</a></p>
    </div>
</div>

<section class="institute-card" aria-labelledby="diagnostics-batch-title">
    <h2 id="diagnostics-batch-title">Batch details</h2>
    <dl>
        <dt>Batch ID</dt><dd>{{ $report['batch']['id'] }}</dd>
        <dt>Batch key</dt><dd>{{ $report['batch']['batch_key'] }}</dd>
        <dt>Batch status</dt><dd>{{ $report['batch']['status'] }}</dd>
        <dt>Source status</dt><dd>{{ $report['batch']['source_status'] ?? 'Not available' }}</dd>
    </dl>
</section>

<section aria-labelledby="diagnostics-summary-title">
    <h2 id="diagnostics-summary-title">Summary</h2>
    <div class="filter-grid">
        @foreach($report['summary'] as $label => $value)
            <div class="institute-card">
                <strong>{{ ucfirst(str_replace('_', ' ', $label)) }}</strong>
                <div>{{ $value }}</div>
            </div>
        @endforeach
    </div>
</section>

<section aria-labelledby="diagnostics-rows-title">
    <h2 id="diagnostics-rows-title">Row-by-row blockers</h2>
    <p>Use the blocker codes below to decide what requires review. Do not approve or publish a row solely because it appears in this report.</p>
    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>Row</th><th>Institution</th><th>Row status</th><th>Identity decision</th>
                    <th>Active evidence</th><th>Open conflicts</th><th>Canonical status</th><th>Possible duplicate</th><th>Blockers</th><th>Validation errors</th>
                </tr>
            </thead>
            <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td>{{ $row['row_number'] }} <small>(ID {{ $row['row_id'] }})</small></td>
                    <td>{{ $row['institution_name'] ?? 'Name missing' }}</td>
                    <td>{{ $row['row_status'] }}</td>
                    <td>{{ $row['identity_decision'] ?? 'Not recorded' }}</td>
                    <td>{{ $row['evidence_active'] ? 'Yes' : 'No' }}</td>
                    <td>{{ $row['open_conflicts'] }}</td>
                    <td>{{ $row['canonical_status'] ?? 'No canonical institution' }}</td>
                    <td>
                        @if(!empty($row['possible_duplicate_institution_id']))
                            {{ $row['possible_duplicate_institution_name'] }} (ID {{ $row['possible_duplicate_institution_id'] }}, {{ $row['possible_duplicate_institution_status'] }})
                        @else
                            None detected
                        @endif
                    </td>
                    <td>
                        @if(count($row['blockers']))
                            <ul>@foreach($row['blockers'] as $blocker)<li><code>{{ $blocker }}</code></li>@endforeach</ul>
                        @else
                            None identified
                        @endif
                    </td>
                    <td>
                        @if(count((array) $row['validation_errors']))
                            <ul>@foreach((array) $row['validation_errors'] as $error)<li>{{ is_scalar($error) ? $error : json_encode($error) }}</li>@endforeach</ul>
                        @else
                            None recorded
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10">This batch contains no import rows.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
