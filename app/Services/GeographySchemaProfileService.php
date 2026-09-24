<?php
namespace App\Services;
use App\Models\ImportSchemaProfile;
use RuntimeException;
class GeographySchemaProfileService {
 public function geography(string $version='1.0'): ImportSchemaProfile {
  if($version!=='1.0') throw new RuntimeException("Unsupported geography import schema version: {$version}");
  return ImportSchemaProfile::firstOrCreate(['profile_key'=>'geography-csv','version'=>$version],[
   'entity_type'=>'geography','status'=>'active',
   'columns'=>['name','slug','type','code','parent_slug','parent_type','status'],
   'required_columns'=>['name','slug','type','parent_slug','parent_type'],
   'normalization_rules'=>['trim_strings'=>true,'lowercase_slug'=>true],
   'description'=>'Governed geography CSV schema requiring an existing parent for non-country records.'
  ]);
 }
 public function validateHeaders(array $headers,ImportSchemaProfile $profile): array {
  $headers=array_values(array_unique(array_map(fn($v)=>trim((string)$v),$headers)));$errors=[];
  foreach($headers as $h)if(!in_array($h,$profile->columns??[],true))$errors[]="Unsupported column: {$h}";
  foreach($profile->required_columns??[] as $h)if(!in_array($h,$headers,true))$errors[]="Required column missing: {$h}";
  return $errors;
 }
 public function template(ImportSchemaProfile $profile): string{return implode(',',$profile->columns)."
";}
}