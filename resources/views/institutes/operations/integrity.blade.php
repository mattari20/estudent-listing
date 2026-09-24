@extends('layouts.app')

@section('title', 'Geography Integrity Review | eStudent.pk')

@section('content')
<section class="page-header">
    <div>
        <p class="eyebrow">Operations</p>
        <h1>Geography Integrity Review</h1>
        <p>Read-only review of active institutions and campus geography integrity.</p>
    </div>
    <a class="button button--ghost" href="{{ route('institutes.operations.queue') }}">Back to operations</a>
</section>

@forelse($institutions as $institution)
    <article class="institute-card">
        <h2>{{ $institution->name }}</h2>
        @if($institution->integrity_findings->isEmpty())
            <p>✓ No geography integrity findings.</p>
        @else
            <ul>
                @foreach($institution->integrity_findings as $finding)
                    @if($finding['type'] !== 'valid_geography')
                        <li>
                            <strong>{{ $finding['type'] }}</strong>
                            @if(isset($finding['message'])) — {{ $finding['message'] }} @endif
                            @if(isset($finding['campus_id'])) · Campus #{{ $finding['campus_id'] }} @endif
                            @if(isset($finding['geography_id'])) · Geography #{{ $finding['geography_id'] }} @endif
                        </li>
                    @endif
                @endforeach
            </ul>
        @endif
    </article>
@empty
    <p>No active institutions.</p>
@endforelse

{{ $institutions->links() }}
@endsection
