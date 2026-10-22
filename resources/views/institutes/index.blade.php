@extends('layouts.app')
@section('title', 'Institutes Directory | eStudent.pk')
@section('description', 'Browse educational institutes and institutions in the eStudent.pk directory.')
@section('canonical', url('/institutes/'))
@section('content')
<section class="hero">
    <p class="eyebrow">Education directory</p>
    <h1>Find educational institutes in Pakistan</h1>
    <p class="hero__text">Search institutions, programs, campuses and admissions information from the eStudent.pk directory.</p>
    <form class="hero-search" method="get" action="{{ route('institutes.search') }}" role="search">
        <label class="sr-only" for="directory-search">Search institutes</label>
        <input id="directory-search" name="q" type="search" placeholder="Search institute, program or city" autocomplete="off">
        <button type="submit">Search</button>
    </form>
</section>

<section class="section-heading directory-section-heading">
    <div>
        <p class="eyebrow">Directory</p>
        <h2>Educational institutes</h2>
        <p class="directory-results-summary" aria-live="polite">
            @if ($institutions->total() > 0)
                Showing <strong>{{ number_format($institutions->firstItem()) }}–{{ number_format($institutions->lastItem()) }}</strong>
                of <strong>{{ number_format($institutions->total()) }}</strong> institutions
            @else
                No institutions to display
            @endif
        </p>
    </div>
    <div class="directory-toolbar">
        <form class="directory-page-size" method="get" action="{{ route('institutes.index') }}">
            <label for="directory-per-page">Institutions per page</label>
            <select id="directory-per-page" name="per_page" onchange="this.form.submit()">
                @foreach ([9, 12, 24, 48] as $pageSize)
                    <option value="{{ $pageSize }}" @selected((int) request()->query('per_page', 24) === $pageSize)>{{ $pageSize }} per page</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="button button--ghost">Apply</button></noscript>
        </form>
        <a class="text-link" href="{{ route('institutes.search') }}">Advanced search →</a>
    </div>
</section>

<section class="card-grid" aria-label="Institute results">
    @forelse ($institutions as $institution)
        <article class="institute-card">
            <span class="badge">{{ $institution->institution_type ?: 'Educational institute' }}</span>
            <h3><a href="{{ route('institutes.show', $institution) }}">{{ $institution->name }}</a></h3>
            @if ($institution->official_name && $institution->official_name !== $institution->name)
                <p class="muted">{{ $institution->official_name }}</p>
            @endif
            <a class="card-link" href="{{ route('institutes.show', $institution) }}">View institute →</a>
        </article>
    @empty
        <div class="empty-state">
            <h3>No institutions available</h3>
            <p>Institution information is not currently available.</p>
        </div>
    @endforelse
</section>

@if ($institutions->total() > 0)
    <nav class="directory-pagination" aria-label="Institution directory pages">
        <div class="directory-pagination__meta">
            Page <strong>{{ $institutions->currentPage() }}</strong> of <strong>{{ $institutions->lastPage() }}</strong>
        </div>
        <div class="pagination-wrap">{{ $institutions->onEachSide(1)->links() }}</div>
    </nav>
@endif
@endsection
