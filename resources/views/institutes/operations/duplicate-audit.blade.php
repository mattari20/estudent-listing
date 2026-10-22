@extends('layouts.app')

@section('title', 'Cross-batch Duplicate Audit')

@section('content')
<div class="page-header">
    <div>
        <h1>Cross-batch Duplicate Audit</h1>
        <p>Read-only review across institution import batches. Viewing this report does not change data.</p>
        <p><a href="{{ route('institutes.operations.queue') }}">← Return to operations queue</a></p>
    </div>
</div>

<section class="institute-card" aria-labelledby="audit-summary">
    <h2 id="audit-summary">Audit summary</h2>
    <div class="filter-grid">
        @foreach($report['summary'] as $label => $value)
            <div class="institute-card">
                <strong>{{ ucfirst(str_replace('_', ' ', $label)) }}</strong>
                <div>{{ $value }}</div>
            </div>
        @endforeach
    </div>
    <p><strong>Safety:</strong> {{ $report['warning'] }}</p>
</section>

<section class="institute-card" aria-labelledby="canonical-collisions">
    <h2 id="canonical-collisions">Canonical institution collision review</h2>
    <p>Read-only checks for multiple canonical institution IDs sharing a normalized name or slug. These are review candidates, not automatic deletion instructions.</p>
    <h3>Name / alias collisions ({{ count($report['canonical_identity_collisions'] ?? []) }})</h3>
    @forelse(($report['canonical_identity_collisions'] ?? []) as $collision)
        <div class="institute-card">
            <strong>Normalized identity: <code>{{ $collision['normalized_identity'] }}</code></strong>
            <ul>@foreach($collision['institutions'] as $institution)
                <li>ID {{ $institution['id'] }} — {{ $institution['name'] }}; slug {{ $institution['slug'] }}; status {{ $institution['status'] }}</li>
            @endforeach</ul>
        </div>
    @empty
        <p>No canonical name collisions detected.</p>
    @endforelse
    <h3>Slug collisions ({{ count($report['canonical_slug_collisions'] ?? []) }})</h3>
    @forelse(($report['canonical_slug_collisions'] ?? []) as $collision)
        <div class="institute-card">
            <strong>Normalized slug: <code>{{ $collision['normalized_slug'] }}</code></strong>
            <ul>@foreach($collision['institutions'] as $institution)
                <li>ID {{ $institution['id'] }} — {{ $institution['name'] }}; slug {{ $institution['slug'] }}; status {{ $institution['status'] }}</li>
            @endforeach</ul>
        </div>
    @empty
        <p>No canonical slug collisions detected.</p>
    @endforelse
    <p><strong>Cleanup mode:</strong> Preview only. No rows or institutions are changed.</p>
</section>

<section aria-labelledby="audit-rows">
    <h2 id="audit-rows">Rows requiring review</h2>
    <div style="overflow-x:auto">
        <table>
            <thead><tr>
                <th>Import row</th><th>Batch</th><th>Institution name</th><th>Status</th>
                <th>Issues / validation errors</th><th>Canonical match</th><th>Duplicate of</th><th>Suggested review</th>
            </tr></thead>
            <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td>ID {{ $row['row_id'] }} / row {{ $row['row_number'] }}</td>
                    <td>{{ $row['batch_id'] }} — {{ $row['batch_key'] }}</td>
                    <td>{{ $row['institution_name'] ?? 'Name missing' }}</td>
                    <td>{{ $row['row_status'] }}</td>
                    <td><ul>@foreach($row['issues'] as $issue)<li><code>{{ $issue }}</code></li>@endforeach</ul>@if(!empty($row['validation_errors']))<details><summary>Validation details</summary><pre>{{ json_encode($row['validation_errors'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>@endif</td>
                    <td>
                        @if($row['canonical_match'])
                            {{ $row['canonical_match']['name'] }} (ID {{ $row['canonical_match']['id'] }}, {{ $row['canonical_match']['status'] }})
                        @else
                            None detected
                        @endif
                    </td>
                    <td>
                        @if($row['duplicate_of'])
                            Row ID {{ $row['duplicate_of']['row_id'] }}, batch {{ $row['duplicate_of']['batch_id'] }} ({{ $row['duplicate_of']['name'] ?: 'Name missing' }})
                        @else
                            None detected
                        @endif
                    </td>
                    <td>{{ str_replace('_', ' ', $row['recommended_action']) }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No duplicate or incomplete rows detected by exact-name/slug matching.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
