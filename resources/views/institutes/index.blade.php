@extends('layouts.app')

@section('title', 'Institutes Directory | eStudent.pk')
@section('description', 'Browse educational institutes and institutions in the eStudent.pk directory.')
@section('canonical', url('/institutes/'))

@section('content')
<section>
    <h1>Institutes Directory</h1>
    <p>Browse educational institutions listed by eStudent.pk.</p>

    <form method="get" action="{{ route('institutes.search') }}" role="search">
        <label for="directory-search">Search institutes</label>
        <input id="directory-search" name="q" type="search" placeholder="Search by institute name">
        <button type="submit">Search</button>
    </form>
</section>

<section aria-label="Institute results">
@forelse ($institutions as $institution)
    <article>
        <h2>
            <a href="{{ route('institutes.show', $institution) }}">{{ $institution->name }}</a>
        </h2>
        @if ($institution->institution_type)
            <p>{{ $institution->institution_type }}</p>
        @endif
    </article>
@empty
    <p>No institutions are currently available.</p>
@endforelse
</section>

{{ $institutions->links() }}
@endsection
