@extends('layouts.app')

@section('title','Geography Integrity Review')
@section('content')
<div class="page-header"><h1>Geography Integrity Review</h1><a href="{{ route('institutes.operations.queue') }}">Back to operations</a></div>
<p>Read-only review of active institutions and their campus geography integrity findings.</p>
@forelse($institutions as $institution)
<article class="institute-card">
<h2>{{ $institution->name }}</h2>
@if($institution->integrity_findings->isEmpty())
<p>✓ No geography integrity findings.</p>
@else
<ul>
@foreach($institution->integrity_findings as $finding)
<li><strong>{{ $finding['type'] }}</strong>@if(isset($finding['message'])) — {{ $finding['message'] }}@endif
@if(isset($finding['campus_id'])) · Campus #{{ $finding['campus_id'] }}@endif
@if(isset($finding['geography_id'])) · Geography #{{ $finding['geography_id'] }}@endif
</li>
@endforeach
</ul>
@endif
</article>
@empty
<p>No active institutions.</p>
@endforelse
{{ $institutions->links() }}
@endsection
