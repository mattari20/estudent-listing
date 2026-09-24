<?php
namespace App\Services;
use App\Models\Geography;
use App\Models\ImportRow;
class GeographyImportResolverService {
 public function resolve(ImportRow $row): array {
  $payload=$row->normalized_payload??$row->payload??[];
  $type=(string)($payload['type']??''); $slug=(string)($payload['slug']??'');
  $query=Geography::query()->where('type',$type)->where('slug',$slug);
  $matches=$query->get();
  $state=$matches->count()===1?'matched':($matches->count()>1?'conflict':'new_candidate');
  $row->update(['match_state'=>$state,'canonical_subject_type'=>$matches->count()===1?Geography::class:null,'canonical_subject_id'=>$matches->count()===1?$matches->first()->id:null]);
  return ['state'=>$state,'matches'=>$matches];
 }
}