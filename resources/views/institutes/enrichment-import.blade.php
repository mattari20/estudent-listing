@extends('layouts.app')

@section('title', 'Institute Enrichment Import | eStudent.pk')

@section('content')
@include('institutes.operations._admin-navigation')
<section class="search-header">
    <p class="eyebrow">Data operations</p>
    <h1>Institute enrichment import</h1>
    <p>Use this controlled workflow to add campus or program data to an existing canonical institution.</p>
    <p><strong>Current phase:</strong> Campus and Program enrichment.</p>
</section>

<form class="filter-panel" method="post" action="{{ route('institutes.operations.enrichment-import.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="filter-grid">
        <div class="field">
            <label for="parent_institution_id">Parent institution</label>
            <select id="parent_institution_id" name="parent_institution_id" required>
                <option value="">Select institution</option>
                @foreach($institutions as $institution)
                    <option value="{{ $institution->id }}" @selected(old('parent_institution_id') == $institution->id)>
                        {{ $institution->name }} (ID {{ $institution->id }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="entity_type">Enrichment type</label>
            <select id="entity_type" name="entity_type" required>
                <option value="campus" @selected(old('entity_type', 'campus') === 'campus')>Campus</option>
                <option value="program" @selected(old('entity_type') === 'program')>Program</option>
            </select>
        </div>

        <div class="field">
            <label for="source_name">Source name</label>
            <input id="source_name" name="source_name" value="{{ old('source_name') }}" placeholder="e.g. COMSATS Lahore test enrichment" required>
        </div>

        <div class="field">
            <label for="source_type">Source type</label>
            <input id="source_type" name="source_type" value="{{ old('source_type', 'test') }}" required>
        </div>

        <div class="field">
            <label for="source_url">Source URL</label>
            <input id="source_url" name="source_url" value="{{ old('source_url') }}" type="url">
        </div>

        <div class="field">
            <label for="schema_version">Schema version</label>
            <input id="schema_version" name="schema_version" value="1.0" readonly>
        </div>

        <div class="field field--wide">
            <label for="csv">CSV file</label>
            <input id="csv" name="csv" type="file" accept=".csv,.txt" required>
        </div>
    </div>

    <p class="muted">
        Campus columns: <code>name, slug, campus_type, status, address, postal_code, latitude, longitude</code>.
        Program columns: <code>name, slug, program_level, award, discipline, status, description</code>.
    </p>

    <p>
        <a href="{{ route('institutes.operations.enrichment-import.template', ['entity_type' => 'campus']) }}">Download campus template</a>
        ·
        <a href="{{ route('institutes.operations.enrichment-import.template', ['entity_type' => 'program']) }}">Download program template</a>
    </p>

    @if($errors->any())
        <div class="empty-state">
            <strong>Enrichment import could not be accepted.</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <button type="submit">Validate enrichment import</button>
</form>
@endsection
