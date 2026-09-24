@extends('layouts.app')
@section('title','Institute Operations Queue')
@section('content')
<div class="page-header"><h1>Import Operations</h1><form method="post" action="{{ route('institutes.logout') }}">@csrf<button type="submit">Sign out</button></form></div>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
<h2>Import batches</h2>
@forelse($batches as $batch)<article class="institute-card"><h3>Batch {{ $batch->batch_key }}</h3><p>{{ $batch->source?->name }} · {{ $batch->status }} · {{ $batch->total_rows }} rows</p><a href="{{ route('institutes.operations.queue',['batch_id'=>$batch->id]) }}">Review batch</a></article>@empty<p>No import batches.</p>@endforelse
{{ $batches->links() }}
@if($qualityReport)<h2>Import quality report</h2><div class="filter-grid">@foreach($qualityReport as $key=>$value)<div class="institute-card"><strong>{{ ucfirst(str_replace('_',' ',$key)) }}</strong><div>{{ is_float($value) ? number_format($value*100,2).'%' : $value }}</div></div>@endforeach</div>@endif
@if($summary)<h2>Batch reconciliation</h2><div class="filter-grid">@foreach($summary as $key=>$value)<div class="institute-card"><strong>{{ ucfirst(str_replace('_',' ',$key)) }}</strong><div>{{ $value }}</div></div>@endforeach</div>@endif
<h2>Reverification queue</h2>@forelse($reverification as $item)<article class="institute-card"><strong>{{ $item->reason }}</strong><p>{{ $item->priority }} · {{ $item->status }} · Subject {{ class_basename($item->subject_type) }} #{{ $item->subject_id }}</p><p>Observed {{ $item->observed_at }}</p><form method="post" action="{{ route('institutes.operations.reverification.start',$item) }}">@csrf<input name="reason" required placeholder="Review reason"><button type="submit">Start review</button></form></article>@empty<p>No open reverification items.</p>@endforelse<h2>Review rows</h2>
<form method="post" action="{{ route('institutes.operations.bulk-promote') }}">@csrf
@forelse($rows as $row)<article class="institute-card"><label><input type="checkbox" name="row_ids[]" value="{{ $row->id }}"> <strong>{{ $row->normalized_payload['name'] ?? $row->payload['name'] ?? 'Unnamed' }}</strong></label><p>Row {{ $row->row_number }} · {{ $row->status }} · {{ $row->match_state }}</p><a href="{{ route('institutes.operations.row',$row) }}">Review row</a></article>@empty<p>No import candidates require review.</p>@endforelse
<button type="submit">Promote selected approved rows</button></form>{{ $rows->links() }}
@endsection