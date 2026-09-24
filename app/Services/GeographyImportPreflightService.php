<?php
namespace App\Services;
use App\Models\Geography;
class GeographyImportPreflightService {
 public function checkParent(string $parentSlug,string $parentType): ?int {
  return Geography::where('slug',$parentSlug)->where('type',$parentType)->value('id');
 }
 public function duplicateCount(array $rows): int {
  $seen=[];$duplicates=0;
  foreach($rows as $row){$key=($row['type']??'').'|'.($row['slug']??'');if(isset($seen[$key]))$duplicates++;else$seen[$key]=true;}
  return $duplicates;
 }
}