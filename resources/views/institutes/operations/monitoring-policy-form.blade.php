@extends('layouts.app')
@section('title','Monitoring Policy')
@section('content')
<h1>Create Monitoring Policy</h1>
<form method="post" action="{{ route('institutes.operations.monitoring.policies.store') }}">@csrf
<label>Name <input name="name" value="{{ old('name') }}" required></label>
<label>Source <select name="source_id"><option value="">All active sources</option>@foreach($sources as $source)<option value="{{ $source->id }}">{{ $source->name }}</option>@endforeach</select></label>
<label>Subject type <input name="subject_type"></label><label>Attribute <input name="attribute"></label>
<label>Fresh after days <input type="number" name="fresh_after_days" value="30" min="0"></label>
<label>Stale after days <input type="number" name="stale_after_days" value="90" min="1"></label>
<label>Priority <select name="priority">@foreach(['low','normal','high','urgent'] as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select></label>
<label>Cadence <select name="cadence">@foreach(['hourly','daily','weekly','monthly'] as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select></label>
<label>Status <select name="status">@foreach(['active','paused','draft'] as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select></label>
<button type="submit">Save policy</button></form>
@endsection