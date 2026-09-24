@extends('layouts.app')
@section('title','Monitoring History')
@section('content')
<h1>Monitoring History</h1><h2>Retrieval runs</h2>@forelse($runs as $run)<article class="institute-card"><strong>{{ $run->source?->name }}</strong><p>{{ $run->status }} · HTTP {{ $run->http_status ?? '—' }} · {{ $run->started_at }}</p>@if($run->error_message)<p>{{ $run->error_message }}</p>@endif</article>@empty<p>No retrieval runs.</p>@endforelse{{ $runs->links() }}
<h2>Source changes</h2>@forelse($changes as $change)<article class="institute-card"><strong>{{ $change->change_state }}</strong> · {{ $change->materiality }}<p>{{ $change->summary }}</p><small>{{ $change->observed_at }}</small></article>@empty<p>No source change observations.</p>@endforelse{{ $changes->links() }}
@endsection