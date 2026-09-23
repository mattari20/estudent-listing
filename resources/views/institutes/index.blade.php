@extends('layouts.app')
@section('title', 'Institutes | eStudent.pk')
@section('content')
<h1>Institutes Directory</h1>
@forelse ($institutions as $institution)
<article>
<h2><a href="{{ route('institutes.show', $institution) }}">{{ $institution->name }}</a></h2>
@if ($institution->institution_type)<p>{{ $institution->institution_type }}</p>@endif
</article>
@empty
<p>No institutions are currently available.</p>
@endforelse
{{ $institutions->links() }}
@endsection
