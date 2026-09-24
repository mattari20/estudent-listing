@extends('layouts.app')
@section('title','Geography Import Result | eStudent.pk')
@section('content')
<h1>Geography import received</h1>
<p>Batch: {{ $batch->batch_key }}</p>
<p>Status: {{ $batch->status }}</p>
<p>Total rows: {{ $batch->total_rows }} | Valid candidates: {{ $batch->valid_rows }} | Invalid: {{ $batch->invalid_rows }}</p>
<p>No geography record has been published by this upload alone.</p>
@endsection