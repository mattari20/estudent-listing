<?php
namespace App\Services;
use App\Models\Campus;
use App\Models\Program;
use App\Models\ProgramOffering;
use App\Models\EnrichmentImportRow;
use RuntimeException;
class StructuredEnrichmentPromoter {
 public function promote(EnrichmentImportRow $row): object {
  if($row->status!=='approved') throw new RuntimeException('Only approved enrichment rows may be promoted.');
  $p=$row->normalized_payload??$row->payload;
  return match($row->entity_type){
   'campus'=>Campus::updateOrCreate(['institution_id'=>$p['institution_id'],'slug'=>$p['slug']],$this->campus($p)),
   'program'=>Program::updateOrCreate(['institution_id'=>$p['institution_id'],'slug'=>$p['slug']],$this->program($p)),
   'offering'=>ProgramOffering::updateOrCreate(['program_id'=>$p['program_id'],'campus_id'=>$p['campus_id'],'delivery_mode'=>$p['delivery_mode'],'intake_label'=>$p['intake_label']??null],$this->offering($p)),
   default=>throw new RuntimeException('Unsupported enrichment entity type.')
  };
 }
 private function campus(array $p): array { return array_intersect_key($p,array_flip(['institution_id','geography_id','name','slug','campus_type','status','address','postal_code','latitude','longitude'])); }
 private function program(array $p): array { return array_intersect_key($p,array_flip(['institution_id','name','slug','program_level','award','discipline','status','description'])); }
 private function offering(array $p): array { return array_intersect_key($p,array_flip(['program_id','campus_id','delivery_mode','status','intake_label','duration','availability','effective_from','effective_until'])); }
}