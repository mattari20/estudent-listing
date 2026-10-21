@extends('layouts.app')
@section('title', 'Batch Institution Evidence Capture')
@section('content')
@include('institutes.operations._admin-navigation')
<p><a href="{{ route('institutes.operations.queue', ['batch_id' => $importBatch->id]) }}">← Return to import batch</a></p>
<h1>Batch evidence capture</h1>
<p>Batch #{{ $importBatch->id }} · {{ $importBatch->total_rows }} rows</p>
<p>Upload one CSV containing verified official-source URLs and excerpts copied from those pages. This action records evidence only; it does not promote or publish institutions.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<h2>Eligible candidate rows</h2>
<table>
<thead><tr><th>Row ID</th><th>Row number</th><th>Institution</th></tr></thead>
<tbody>
@forelse($rows as $row)
@php($payload = $row->normalized_payload ?? $row->payload ?? [])
<tr><td>{{ $row->id }}</td><td>{{ $row->row_number }}</td><td>{{ $payload['official_name'] ?? $payload['name'] ?? 'Unnamed' }}</td></tr>
@empty
<tr><td colspan="3">No candidate rows are available in this batch.</td></tr>
@endforelse
</tbody>
</table>
<h2>CSV format</h2>
<p>Use the exact header and column order below. Row IDs must belong to this batch, have an approved identity decision, and have no open conflicts. URLs must use HTTPS. Excerpts must be copied from the verified official page, not generated.</p>
<pre>import_row_id,official_source_url,source_excerpt
38,https://official-university-domain.example/about,"Replace with a real excerpt copied from the official page, at least 20 characters."</pre>
<p><strong>Do not upload the example line above as real evidence.</strong> Replace it with actual verified evidence for each row.</p>
<form method="post" action="{{ route('institutes.operations.imports.evidence-capture.store', $importBatch) }}" enctype="multipart/form-data">
@csrf
<label for="evidence_csv">Evidence CSV file</label>
<input id="evidence_csv" type="file" name="evidence_csv" accept=".csv,text/csv" required>
<button type="submit" onclick="return confirm('Record evidence for all rows in this CSV? The application validates every row before writing. No promotion or publication will occur.');">Validate and capture evidence</button>
</form>
@endsection
