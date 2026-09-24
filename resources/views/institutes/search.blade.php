@extends('layouts.app')

@section('title', $query !== '' ? 'Search: '.$query.' | eStudent.pk' : 'Search Institutes | eStudent.pk')
@section('description', 'Search the eStudent.pk educational institutes directory.')
@if ($query === '')
@section('canonical', url('/institutes/search'))
@endif

@section('content')
<main>
    <h1>Institute Search</h1>

    <form method="get" action="{{ route('institutes.search') }}" role="search">
        <label for="q">Search institutes</label>
        <input id="q" name="q" type="search" value="{{ $query }}" autocomplete="off">
        <button type="submit">Search</button>
    </form>

    @if ($query !== '')
        <p>Results for “{{ $query }}”</p>
    @endif

    <ul>
    @forelse ($institutions as $institution)
        <li>
            <a href="{{ route('institutes.show', $institution) }}">{{ $institution->name }}</a>
            @if ($institution->institution_type)
                — {{ $institution->institution_type }}
            @endif
            <small>
                {{ $institution->campuses_count }} campuses ·
                {{ $institution->programs_count }} programs
            </small>
        </li>
    @empty
        <li>No matching institutes found.</li>
    @endforelse
    </ul>

    {{ $institutions->links() }}
</main>
@endsection
