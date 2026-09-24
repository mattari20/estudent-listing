@extends('layouts.app')

@section('title', $query !== '' ? 'Search: '.$query.' | eStudent.pk' : 'Search Institutes | eStudent.pk')
@section('description', 'Search and filter the eStudent.pk educational institutes directory.')
@if ($query === '' && $type === '' && $city === '' && $program === '')
@section('canonical', url('/institutes/search'))
@endif

@section('content')
<main>
    <h1>Institute Search</h1>

    <form method="get" action="{{ route('institutes.search') }}" role="search">
        <label for="q">Search institutes, programs or cities</label>
        <input id="q" name="q" type="search" value="{{ $query }}" autocomplete="off">

        <label for="type">Institute type</label>
        <select id="type" name="type">
            <option value="">All types</option>
            @foreach ($types as $option)
                <option value="{{ $option }}" @selected($type === $option)>{{ $option }}</option>
            @endforeach
        </select>

        <label for="city">City</label>
        <select id="city" name="city">
            <option value="">All cities</option>
            @foreach ($cities as $option)
                <option value="{{ $option }}" @selected($city === $option)>{{ $option }}</option>
            @endforeach
        </select>

        <label for="program">Program / discipline</label>
        <input id="program" name="program" type="search" value="{{ $program }}">

        <button type="submit">Search</button>
        @if ($query !== '' || $type !== '' || $city !== '' || $program !== '')
            <a href="{{ route('institutes.search') }}">Clear filters</a>
        @endif
    </form>

    @if ($query !== '' || $type !== '' || $city !== '' || $program !== '')
        <p>Showing results for the selected search and filters.</p>
    @endif

    <ul>
    @forelse ($institutions as $institution)
        <li>
            <a href="{{ route('institutes.show', $institution) }}">{{ $institution->name }}</a>
            @if ($institution->institution_type)
                — {{ $institution->institution_type }}
            @endif
            <small>{{ $institution->campuses_count }} campuses · {{ $institution->programs_count }} programs</small>
        </li>
    @empty
        <li>No matching institutes found.</li>
    @endforelse
    </ul>

    {{ $institutions->links() }}
</main>
@endsection
