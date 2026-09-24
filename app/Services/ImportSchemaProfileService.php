<?php
namespace App\Services;
use App\Models\ImportSchemaProfile;
use RuntimeException;
class ImportSchemaProfileService {
 public function institution(string $version='1.0'): ImportSchemaProfile {
  return ImportSchemaProfile::firstOrCreate(
   ['profile_key'=>'institution-csv','version'=>$version],
   ['entity_type'=>'institution','status'=>'active','columns'=>['name','official_name','short_name','slug','institution_type','status'],'required_columns'=>['name'],'normalization_rules'=>['trim_strings'=>true,'generate_slug_from_name'=>true],'description'=>'Governed institution CSV import schema.']
  );
 }
 public function validateHeaders(array $headers, ImportSchemaProfile $profile): array {
  $headers=array_values(array_unique(array_map(fn($v)=>trim((string)$v),$headers)));
  $allowed=$profile->columns??[]; $required=$profile->required_columns??[];
  $errors=[];
  foreach($headers as $h) if(!in_array($h,$allowed,true)) $errors[]="Unsupported column: {$h}";
  foreach($required as $h) if(!in_array($h,$headers,true)) $errors[]="Required column missing: {$h}";
  return $errors;
 }
 public function template(ImportSchemaProfile $profile): string {
  return implode(',', $profile->columns)."\n";
 }
}