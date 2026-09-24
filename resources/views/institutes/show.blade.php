@extends('layouts.app')

@section('title', $institution->name . ' | eStudent.pk')
@section('description', 'Profile, campuses, programs, admissions and available educational information for '.$institution->name.' in the eStudent.pk institutes directory.')
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
                @if ($campus->geography)<p>Location: {{ $campus->geography->name }}</p>@endif
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

                @forelse ($program->offerings as $offering)
                    <section>
                        <h4>Offering{{ $offering->intake_label ? ': '.$offering->intake_label : '' }}</h4>
                        <p>Delivery: {{ $offering->delivery_mode }}</p>
                        @if ($offering->duration)<p>Duration: {{ $offering->duration }}</p>@endif

                        @forelse ($offering->admissionIntakes as $intake)
                            <section>
                                <h5>Admission: {{ $intake->label }}</h5>
                                <p>Status: {{ $intake->status }}</p>
                                @if ($intake->application_open_at)<p>Applications open: {{ $intake->application_open_at->format('Y-m-d') }}</p>@endif
                                @if ($intake->application_close_at)<p>Applications close: {{ $intake->application_close_at->format('Y-m-d') }}</p>@endif
                                @if ($intake->application_url)
                                    <p><a href="{{ $intake->application_url }}" rel="nofollow">Application destination</a></p>
                                @endif

                                @if ($intake->eligibilityRules->isNotEmpty())
                                    <h6>Eligibility information</h6>
                                    <ul>
                                    @foreach ($intake->eligibilityRules as $rule)
                                        <li>
                                            {{ $rule->rule_type }}
                                            @if ($rule->qualification) — {{ $rule->qualification }} @endif
                                            @if ($rule->requirement) — {{ $rule->requirement }} @endif
                                            @if ($rule->minimum_marks !== null) — Minimum marks: {{ $rule->minimum_marks }} @endif
                                        </li>
                                    @endforeach
                                    </ul>
                                @endif

                                @if ($intake->meritRecords->isNotEmpty())
                                    <h6>Published merit information</h6>
                                    <ul>
                                    @foreach ($intake->meritRecords as $merit)
                                        <li>
                                            {{ $merit->merit_type }}
                                            @if ($merit->applicant_category) — {{ $merit->applicant_category }} @endif
                                            @if ($merit->value) — {{ $merit->value }} @endif
                                            @if ($merit->score !== null) — Score: {{ $merit->score }} @endif
                                        </li>
                                    @endforeach
                                    </ul>
                                @endif
                            </section>
                        @empty
                        @endforelse

                        @if ($offering->feeRecords->isNotEmpty())
                            <section>
                                <h5>Fee information</h5>
                                <ul>
                                @foreach ($offering->feeRecords as $fee)
                                    <li>
                                        {{ $fee->fee_type }}:
                                        @if ($fee->amount !== null)
                                            {{ $fee->currency }} {{ $fee->amount }}
                                        @elseif ($fee->minimum_amount !== null || $fee->maximum_amount !== null)
                                            {{ $fee->currency }} {{ $fee->minimum_amount ?? '—' }}–{{ $fee->maximum_amount ?? '—' }}
                                        @else
                                            Amount not published
                                        @endif
                                        @if ($fee->frequency) ({{ $fee->frequency }}) @endif
                                    </li>
                                @endforeach
                                </ul>
                            </section>
                        @endif

                        @if ($offering->scholarships->isNotEmpty())
                            <section>
                                <h5>Scholarships / financial aid</h5>
                                <ul>
                                @foreach ($offering->scholarships as $scholarship)
                                    <li>
                                        <strong>{{ $scholarship->name }}</strong>
                                        @if ($scholarship->benefit_type) — {{ $scholarship->benefit_type }} @endif
                                        @if ($scholarship->eligibility_description) — {{ $scholarship->eligibility_description }} @endif
                                    </li>
                                @endforeach
                                </ul>
                            </section>
                        @endif

                        @if ($offering->accreditations->isNotEmpty())
                            <section>
                                <h5>Recognition / accreditation information</h5>
                                <ul>
                                @foreach ($offering->accreditations as $accreditation)
                                    <li>
                                        {{ $accreditation->name }}
                                        @if ($accreditation->recognition_type) — {{ $accreditation->recognition_type }} @endif
                                        @if ($accreditation->authority_name) — Authority: {{ $accreditation->authority_name }} @endif
                                        — Status: {{ $accreditation->status }}
                                    </li>
                                @endforeach
                                </ul>
                            </section>
                        @endif
                    </section>
                @empty
                    <p>No current program offerings are currently available.</p>
                @endforelse
            </article>
        @empty
            <p>Program information is not currently available.</p>
        @endforelse
    </section>

    @if ($institution->accreditations->isNotEmpty())
        <section aria-labelledby="institution-recognition-heading">
            <h2 id="institution-recognition-heading">Recognition / accreditation information</h2>
            <ul>
            @foreach ($institution->accreditations as $accreditation)
                <li>
                    {{ $accreditation->name }}
                    @if ($accreditation->recognition_type) — {{ $accreditation->recognition_type }} @endif
                    @if ($accreditation->authority_name) — Authority: {{ $accreditation->authority_name }} @endif
                    — Status: {{ $accreditation->status }}
                </li>
            @endforeach
            </ul>
        </section>
    @endif

    <p><small>Information availability varies by institution, program and intake. Missing information does not mean that the institution does not offer it.</small></p>
</article>
@endsection
