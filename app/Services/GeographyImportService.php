<?php
namespace App\Services;
use App\Models\Geography;
use Illuminate\Support\Facades\DB;
class GeographyImportService {
 public function validateRow(array $row): array {
  $required=['name','type','slug'];
  $errors=[];
  foreach($required as $key){if(!isset($row[$key])||trim((string)$row[$key])==='')$errors[]=$key;}
  if(isset($row['type'])&&!in_array($row['type'],['country','province','territory','division','district','tehsil','city','locality','area'],true))$errors[]='type';
  return $errors;
 }
 public function promote(array $row,?int $parentId): Geography {
  return DB::transaction(fn()=>Geography::updateOrCreate(
   ['slug'=>trim($row['slug']),'type'=>$row['type']],
   ['parent_id'=>$parentId,'name'=>trim($row['name']),'code'=>isset($row['code'])&&$row['code']!==''?$row['code']:null,'status'=>$row['status']??'active']
  ));
 }
}