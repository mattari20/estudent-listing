@extends('layouts.app')
@section('content')
<main>
 <h1>Institute Search</h1>
 <form method="get" action="{{ route('institutes.search') }}"><label for="q">Search institutes</label><input id="q" name="q" value="{{ $query }}" autocomplete="off"><button type="submit">Search</button></form>
 @if($query !== '')<p>Results for “{{ $query }}”</p>@endif
 <ul>@forelse($institutions as $institution)<li><a href="{{ route('institutes.show',$institution) }}">{{ $institution->name }}</a></li>@empty<li>No matching institutes found.</li>@endforelse</ul>
 {{ $institutions->links() }}
</main>
@endsection