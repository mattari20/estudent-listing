@extends('layouts.app')
@section('title', $institution->name . ' | eStudent.pk')
@section('content')
<article>
<h1>{{ $institution->name }}</h1>
@if ($institution->institution_type)<p>Type: {{ $institution->institution_type }}</p>@endif
<p>Status: {{ $institution->status }}</p>
</article>
@endsection
