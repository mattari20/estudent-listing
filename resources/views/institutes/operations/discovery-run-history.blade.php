@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1>Discovery run history</h1>
    <p class="text-muted">Recent scheduled discovery outcomes. Candidate records remain review-only until approved through the existing workflow.</p>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Outcome</th>
                    <th>Profile</th>
                    <th>Source</th>
                    <th>Snapshot</th>
                    <th>Candidates</th>
                    <th>Package</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($outcomes as $outcome)
                    <tr>
                        <td>{{ $outcome->occurred_at?->toDateTimeString() ?? '—' }}</td>
                        <td>{{ str($outcome->outcome)->replace('_', ' ')->title() }}</td>
                        <td>{{ $outcome->profile_key ?? '—' }}</td>
                        <td>
                            @if ($outcome->source?->url)
                                <a href="{{ $outcome->source->url }}" target="_blank" rel="noopener noreferrer">Official source</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $outcome->snapshot_id ?? '—' }}</td>
                        <td>{{ $outcome->candidate_count }}</td>
                        <td>
                            @php
                                $batchId = (int) ($outcome->details['batch_id'] ?? 0);
                                $batch = $batches->get($batchId);
                                $snapshotId = (int) ($batch?->rows->first()?->payload['_provenance']['snapshot_id'] ?? 0);
                            @endphp
                            @if ($batch && $snapshotId > 0)
                                <a href="{{ route('institutes.operations.discovery-packages.download', ['importBatch' => $batch, 'file' => 'csv']) }}">CSV</a>
                                <span aria-hidden="true">·</span>
                                <a href="{{ route('institutes.operations.discovery-packages.download', ['importBatch' => $batch, 'file' => 'manifest']) }}">JSON manifest</a>
                            @else
                                {{ $outcome->review_package_status ?? 'No package available' }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No discovery run outcomes have been recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $outcomes->links() }}
</div>
@endsection
