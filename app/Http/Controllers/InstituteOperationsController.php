<?php
namespace App\Http\Controllers;
use App\Models\IdentityMatchCandidate;
use App\Models\ImportRow;
use Illuminate\View\View;
class InstituteOperationsController {
 public function queue(): View {
  $rows=ImportRow::query()->whereIn('status',['candidate','validated'])->with(['batch','batch.source'])->latest()->paginate(30);
  return view('institutes.operations.queue',compact('rows'));
 }
 public function show(ImportRow $importRow): View {
  $importRow->load(['batch.source']);
  $matches=IdentityMatchCandidate::query()->where('import_row_id',$importRow->id)->with('institution')->get();
  return view('institutes.operations.row',compact('importRow','matches'));
 }
}