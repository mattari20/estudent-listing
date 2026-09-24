@extends('layouts.app')

@section('title', $institution->name . ' | eStudent.pk')
@section('description', 'Profile, campuses and programs for '.$institution->name.' in the eStudent.pk institutes directory.')
@section('canonical', route('institutes.show', $institution))

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'EducationalOrganization',
    'name' => $institution->name,
    'url' => route('institutes.show', $institution),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<article>
    <header>
        <h1>{{ $institution->name }}</h1>
        @if ($institution->official_name && $institution->official_name !== $institution->name)
            <p>Official name: {{ $institution->official_name }}</p>
        @endif
        @if ($institution->institution_type)
            <p>Type: {{ $institution->institution_type }}</p>
        @endif
        @if ($institution->description)
            <p>{{ $institution->description }}</p>
        @endif
    </header>

    <section aria-labelledby="campuses-heading">
        <h2 id="campuses-heading">Campuses</h2>
        @forelse ($institution->campuses as $campus)
            <article>
                <h3>{{ $campus->name }}</h3>
                @if ($campus->address)<p>{{ $campus->address }}</p>@endif
                @if ($campus->geography)<p>{{ $campus->geography->name }}</p>@endif
            </article>
        @empty
            <p>Campus information is not currently available.</p>
        @endforelse
    </section>

    <section aria-labelledby="programs-heading">
        <h2 id="programs-heading">Programs</h2>
        @forelse ($institution->programs as $program)
            <article>
                <h3>{{ $program->name }}</h3>
                @if ($program->program_level)<p>Level: {{ $program->program_level }}</p>@endif
                @if ($program->discipline)<p>Discipline: {{ $program->discipline }}</p>@endif
                @if ($program->award)<p>Award: {{ $program->award }}</p>@endif

                @if ($program->offerings->isNotEmpty())
                    <h4>Current offerings</h4>
                    <ul>
                    @foreach ($program->offerings as $offering)
                        <li>
                            {{ $offering->delivery_mode }}
                            @if ($offering->intake_label) — {{ $offering->intake_label }} @endif
                            @if ($offering->duration) — {{ $offering->duration }} @endif
                        </li>
                    @endforeach
                    </ul>
                @endif
            </article>
        @empty
            <p>Program information is not currently available.</p>
        @endforelse
    </section>
</article>
@endsection
