<?php
namespace App\Services;
use Illuminate\Support\Str;
class StructuredEnrichmentValidator {
 public function normalize(string $entityType,array $payload): array {
  $payload=array_map(fn($v)=>is_string($v)?trim($v):$v,$payload);
  if($entityType==='campus'){
   if(empty($payload['institution_id'])||empty($payload['name'])) throw new \InvalidArgumentException('Campus requires institution_id and name.');
   $payload['slug']=$payload['slug']??Str::slug((string)$payload['name']);
  } elseif($entityType==='program'){
   if(empty($payload['institution_id'])||empty($payload['name'])) throw new \InvalidArgumentException('Program requires institution_id and name.');
   $payload['slug']=$payload['slug']??Str::slug((string)$payload['name']);
  } elseif($entityType==='offering'){
   foreach(['program_id','campus_id','delivery_mode'] as $field) if(empty($payload[$field])) throw new \InvalidArgumentException('Offering requires '.$field.'.');
  } else throw new \InvalidArgumentException('Unsupported enrichment entity type.');
  return $payload;
 }
}