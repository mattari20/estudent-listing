@extends('layouts.app')
@section('title','Geography Import | eStudent.pk')
@section('content')
@include('institutes.operations._admin-navigation')
<h1>Geography Import</h1>
<p>Upload source-backed geography data for validation and review. Nothing is published directly.</p>
<p><a href="{{ route('institutes.operations.geography-import.template') }}">Download geography CSV template</a></p>
<form method="post" action="{{ route('institutes.operations.geography-import.store') }}" enctype="multipart/form-data">
@csrf
<label>Source name <input name="source_name" required></label>
<label>Source type <input name="source_type" required></label>
<label>Source URL <input name="source_url" type="url"></label>
<input type="hidden" name="schema_version" value="{{ $profile->version }}">
<label>CSV <input name="csv" type="file" accept=".csv,.txt" required></label>
<button type="submit">Validate geography import</button>
</form>
@endsection