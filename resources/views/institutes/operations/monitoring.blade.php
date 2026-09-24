@extends('layouts.app')
@section('title','Monitoring Operations')
@section('content')
<div class="page-header"><h1>Source Monitoring</h1><a href="{{ route('institutes.operations.queue') }}">Import operations</a></div>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
<h2>Health</h2><div class="filter-grid">@foreach($health as $key=>$value)<div class="institute-card"><strong>{{ ucfirst(str_replace('_',' ',$key)) }}</strong><div>{{ $value }}</div></div>@endforeach</div>
<h2>Active alerts</h2>
@forelse($alerts as $alert)<article class="institute-card"><strong>{{ strtoupper($alert->severity) }}</strong> · {{ $alert->status }}<p>{{ $alert->message }}</p><small>{{ $alert->last_detected_at }}</small>
@if($alert->status==='open')<form method="post" action="{{ route('institutes.operations.monitoring.alerts.acknowledge',$alert) }}">@csrf<button type="submit">Acknowledge</button></form>@endif
<form method="post" action="{{ route('institutes.operations.monitoring.alerts.resolve',$alert) }}">@csrf<button type="submit">Resolve</button></form></article>@empty<p>No active alerts.</p>@endforelse
<h2>Monitoring policies</h2>@forelse($policies as $policy)<article class="institute-card"><strong>{{ $policy->name }}</strong><p>{{ $policy->cadence ?? 'daily' }} · Next {{ $policy->next_run_at ?? 'due' }} · Failures {{ $policy->consecutive_failures }}</p></article>@empty<p>No active policies.</p>@endforelse
<a href="{{ route('institutes.operations.monitoring.history') }}">View monitoring history</a>
@endsection