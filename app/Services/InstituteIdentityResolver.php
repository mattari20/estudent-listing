<?php
namespace App\Services;
use App\Models\IdentityMatchCandidate;
use App\Models\ImportRow;
use App\Models\Institution;
use Illuminate\Support\Str;
class InstituteIdentityResolver {
 public function resolve(ImportRow $row): int {
  $payload=$row->normalized_payload ?? $row->payload ?? [];
  $name=trim((string)($payload['name'] ?? '')); $slug=trim((string)($payload['slug'] ?? ''));
  if($name==='') return 0;
  $matches=[]; if($slug!=='') $matches=Institution::query()->where('slug',$slug)->get();
  if($matches->isEmpty()) $matches=Institution::query()->whereRaw('LOWER(name) = ?', [Str::lower($name)])->get();
  $created=0;
  foreach($matches as $institution){
   $type=$institution->slug===$slug && $slug!==''?'exact_slug':'exact_name';
   $confidence=$type==='exact_slug'?1.0000:0.9800;
   IdentityMatchCandidate::updateOrCreate(['import_row_id'=>$row->id,'institution_id'=>$institution->id],['match_type'=>$type,'confidence'=>$confidence,'status'=>'candidate','reason'=>'Exact governed identity field match.','matched_at'=>now()]);
   $created++;
  }
  $row->update(['match_state'=>$created>0?'matched':'new_candidate']);
  app(ImportConflictDetectionService::class)->detect($row);
  return $created;
 }
}